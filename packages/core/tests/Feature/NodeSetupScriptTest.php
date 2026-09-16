<?php

namespace Tests\Feature;

use Froxlor\Core\Services\Node\Setup\NodeSetupPlan;
use Froxlor\Core\Services\Node\Setup\NodeSetupScript;
use Froxlor\Core\Services\Node\Setup\ServicePlan;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/** Executes only isolated file operations, with systemctl mocked and no apt operations. */
class NodeSetupScriptTest extends TestCase
{
    private string $sandbox;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        if (PHP_OS_FAMILY !== 'Linux' || ! function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Run in the isolated root Docker test container.');
        }
        $this->sandbox = '/var/lib/froxlor-setup-test-'.bin2hex(random_bytes(8));
        mkdir($this->sandbox, 0700);
        mkdir($this->sandbox.'/sbin', 0700);
        mkdir($this->sandbox.'/installed', 0700);
    }

    protected function tearDown(): void
    {
        if (isset($this->sandbox)) {
            (new Filesystem)->deleteDirectory($this->sandbox);
        }
        parent::tearDown();
    }

    public function test_invalid_config_restores_previous_file_without_reloading(): void
    {
        file_put_contents($this->sandbox.'/example.conf', 'previous');
        $process = $this->execute($this->plan('/usr/bin/false'));
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertSame('previous', file_get_contents($this->sandbox.'/example.conf'));
        $this->assertFileDoesNotExist($this->sandbox.'/service-calls');
        $this->assertSame("failed\n", file_get_contents($this->journal().'/status'));
    }

    public function test_successful_run_is_repeatable_without_rewrite_or_reload(): void
    {
        $first = $this->execute($this->plan());
        $this->assertSame(0, $first->getExitCode(), $first->getErrorOutput());
        $this->assertStringContainsString('FROXLOR_SETUP_OK:', $first->getOutput());
        $this->assertSame('candidate', file_get_contents($this->sandbox.'/example.conf'));
        $this->assertStringContainsString('reload example', file_get_contents($this->sandbox.'/service-calls'));
        $inode = fileinode($this->sandbox.'/example.conf');
        file_put_contents($this->sandbox.'/service-calls', '');

        $second = $this->execute($this->plan());
        $this->assertSame(0, $second->getExitCode(), $second->getErrorOutput());
        clearstatcache();
        $this->assertSame($inode, fileinode($this->sandbox.'/example.conf'));
        $this->assertStringNotContainsString('reload example', file_get_contents($this->sandbox.'/service-calls'));
        $this->assertSame("succeeded\n", file_get_contents($this->journal().'/status'));
    }

    public function test_failed_health_check_restores_config_and_reactivates_previous_service(): void
    {
        file_put_contents($this->sandbox.'/example.conf', 'previous');
        $process = $this->execute($this->plan(health: '/usr/bin/false'));
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertSame('previous', file_get_contents($this->sandbox.'/example.conf'));
        $this->assertStringContainsString('reload-or-restart example', file_get_contents($this->sandbox.'/service-calls'));
    }

    public function test_failed_creation_removes_only_the_new_config(): void
    {
        $process = $this->execute($this->plan('/usr/bin/false'));
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertFileDoesNotExist($this->sandbox.'/example.conf');
    }

    public function test_symlink_target_is_rejected_without_touching_referent(): void
    {
        file_put_contents($this->sandbox.'/original', 'untouched');
        symlink($this->sandbox.'/original', $this->sandbox.'/example.conf');
        $process = $this->execute($this->plan());
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertSame('untouched', file_get_contents($this->sandbox.'/original'));
        $this->assertTrue(is_link($this->sandbox.'/example.conf'));
    }

    public function test_unrecovered_run_blocks_next_mutation(): void
    {
        $this->assertSame(0, $this->execute($this->plan())->getExitCode());
        file_put_contents($this->journal().'/status', "running\n");
        file_put_contents($this->sandbox.'/example.conf', 'operator-value');
        $this->assertNotSame(0, $this->execute($this->plan())->getExitCode());
        $this->assertSame('operator-value', file_get_contents($this->sandbox.'/example.conf'));
    }

    public function test_failed_recovery_is_reported_and_blocks_retries(): void
    {
        file_put_contents($this->sandbox.'/example.conf', 'previous');
        $process = $this->execute($this->plan(health: '/usr/bin/false'), failRecovery: true);
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertSame("recovery-required\n", file_get_contents($this->journal().'/status'));
    }

    public function test_package_installs_are_repeatable_and_temporary_policy_is_removed(): void
    {
        $plan = ServicePlan::make()->ensurePackages(['ca-certificates', 'sudo']);
        $first = $this->execute($plan);
        $this->assertSame(0, $first->getExitCode(), $first->getErrorOutput());
        $this->assertFileExists($this->sandbox.'/installed/sudo');
        $this->assertFileDoesNotExist($this->sandbox.'/sbin/policy-rc.d');
        $calls = file_get_contents($this->sandbox.'/apt-calls');
        $this->assertSame(0, $this->execute($plan)->getExitCode());
        $this->assertSame($calls, file_get_contents($this->sandbox.'/apt-calls'));
    }

    public function test_existing_administrator_start_policy_is_preserved(): void
    {
        file_put_contents($this->sandbox.'/sbin/policy-rc.d', 'administrator policy');
        $result = $this->execute(ServicePlan::make()->ensurePackages(['sudo']));
        $this->assertNotSame(0, $result->getExitCode());
        $this->assertSame('administrator policy', file_get_contents($this->sandbox.'/sbin/policy-rc.d'));
        $this->assertFileDoesNotExist($this->sandbox.'/apt-calls');
    }

    public function test_failed_package_install_cleans_up_its_start_policy(): void
    {
        $result = $this->execute(ServicePlan::make()->ensurePackages(['sudo']), failPackages: true);
        $this->assertNotSame(0, $result->getExitCode());
        $this->assertFileDoesNotExist($this->sandbox.'/sbin/policy-rc.d');
        $this->assertSame([], glob($this->sandbox.'/sbin/.froxlor-policy.*'));
        $this->assertSame("failed\n", file_get_contents($this->journal().'/status'));
    }

    private function plan(string $validator = '/usr/bin/true', string $health = '/usr/bin/true'): ServicePlan
    {
        return ServicePlan::make()->config('/etc/froxlor-setup-test.conf', 'candidate')
            ->validateCommand([$validator])->activateService('example')->healthCheck([$health]);
    }

    private function execute(ServicePlan $service, bool $failRecovery = false, bool $failPackages = false): Process
    {
        $this->sequence++;
        $os = parse_ini_file('/etc/os-release');
        $plan = new NodeSetupPlan('fixture', $os['ID'].'@'.$os['VERSION_ID'], [
            'tests/node-setup:fixture' => ['role' => 'fixture', 'revision' => '1', 'plan' => $service],
        ]);
        $script = (new NodeSetupScript)->compile($plan, $this->runId());
        $script = strtr($script, [
            '/var/lib/froxlor' => $this->sandbox.'/state',
            '/etc/froxlor-setup-test.conf' => $this->sandbox.'/example.conf',
            '/usr/bin/true' => '/bin/true',
            '/usr/bin/false' => '/bin/false',
            '/usr/sbin' => $this->sandbox.'/sbin',
        ]);
        // Fixtures contain no secrets; retain stderr to diagnose shell failures in tests.
        $script = str_replace('exec >/dev/null 2>&1', 'set -x', $script);
        // Panel image uses BusyBox: exercise a real nonblocking lock in these short tests.
        // The production script uses util-linux's bounded wait on Debian/Ubuntu.
        $mock = "flock() { shift 2; command flock -n \"\$@\"; }\n"
            .'systemctl() { printf "%s\\n" "$*" >> '.escapeshellarg($this->sandbox.'/service-calls').'; '
            .($failRecovery ? '[ "$1" != reload-or-restart ];' : 'return 0;')." }\n";
        $mock .= 'test_root='.escapeshellarg($this->sandbox)."\n";
        $mock .= <<<'SH'
dpkg-query() {
    [ -f "$test_root/installed/${!#}" ] || return 1
    printf 'install ok installed'
}
apt-get() {
    printf '%s\n' "$*" >> "$test_root/apt-calls"
    local install_mode=0 argument
    for argument in "$@"; do
        if [ "$argument" = install ]; then install_mode=1; continue; fi
        if [ "$install_mode" = 1 ]; then
            [ "$fail_packages" = 0 ] || return 1
            touch "$test_root/installed/$argument"
        fi
    done
}
SH;
        $mock .= "\nfail_packages=".($failPackages ? '1' : '0')."\n";
        $process = new Process(['/bin/bash', '-se']);
        $process->setInput($mock.$script);
        $process->setTimeout(10);
        $process->run();

        return $process;
    }

    private function runId(): string
    {
        return '01K0000000000000000000000'.$this->sequence;
    }

    private function journal(): string
    {
        return $this->sandbox.'/state/node-setup/'.$this->runId();
    }
}
