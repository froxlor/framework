<?php

namespace Tests\Feature\Environment;

use Froxlor\Core\Jobs\Environment\CreateEnvironment;
use Froxlor\Core\Jobs\Environment\DeleteEnvironment;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CreateEnvironmentFakeAdapter::reset();

        if (!in_array(CreateEnvironmentFakeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(CreateEnvironmentFakeAdapter::class);
        }
    }

    public function test_it_skips_occupied_system_guid_and_persists_the_next_free_guid(): void
    {
        CreateEnvironmentFakeAdapter::$resolvedGuid = 10005;

        $tenant = Tenant::query()->firstOrFail();
        $plan = Plan::query()->firstOrFail();
        $node = Node::query()->create([
            'adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Create Environment Test Node',
            'hostname' => 'create-environment-test-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));
        $node->setSetting('node.last_username_number', 4);
        $node->setSetting('node.last_guid_number', 10003);

        $environment = Environment::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'name' => 'Create Environment Test',
        ]);

        CreateEnvironment::dispatchSync($environment->refresh(), $node);

        $pivot = DB::table('node_environments')
            ->where('environment_id', $environment->id)
            ->where('node_id', $node->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame('usr5', $pivot->unix_name);
        $this->assertSame(10005, $pivot->guid);
        $this->assertSame('main', $pivot->mode);
        $this->assertSame(10005, $node->refresh()->getSetting('node.last_guid_number'));
        $this->assertSame(5, $node->getSetting('node.last_username_number'));

        $this->assertStringContainsString('candidate=\'10004\'', CreateEnvironmentFakeAdapter::$guidResolutionCommand);
        $this->assertStringContainsString('JAILUSER="usr5"', CreateEnvironmentFakeAdapter::$uploadedScript);
        $this->assertStringContainsString('GUID="10005"', CreateEnvironmentFakeAdapter::$uploadedScript);
        $this->assertStringContainsString('jk_jailuser -j "$JAILBASE" "$JAILUSER"', CreateEnvironmentFakeAdapter::$uploadedScript);
        $this->assertStringNotContainsString('jk_jailuser -m', CreateEnvironmentFakeAdapter::$uploadedScript);

        $auditLog = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('environment_id', $environment->id)
            ->where('action', 'like', 'environment "% created on node "%')
            ->firstOrFail();

        $this->assertSame($node->id, $auditLog->context['node_id']);
        $this->assertSame('usr5', $auditLog->context['unix_name']);
        $this->assertSame(10005, $auditLog->context['guid']);
        $this->assertArrayNotHasKey('tenant_id', $auditLog->context);
        $this->assertArrayNotHasKey('environment_id', $auditLog->context);
    }

    public function test_environment_delete_removes_jail_from_assigned_node(): void
    {
        $tenant = Tenant::query()->firstOrFail();
        $plan = Plan::query()->firstOrFail();
        $node = Node::query()->create([
            'adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Delete Environment Test Node',
            'hostname' => 'delete-environment-test-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));

        $environment = Environment::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'name' => 'Delete Environment Test',
        ]);
        $environment->nodes()->attach($node, [
            'unix_name' => 'usr7',
            'guid' => 10007,
            'mode' => 'main',
        ]);

        DeleteEnvironment::dispatchSync($environment->refresh());

        $this->assertDatabaseMissing('node_environments', [
            'environment_id' => $environment->id,
            'node_id' => $node->id,
        ]);

        $deleteCommands = implode(PHP_EOL, CreateEnvironmentFakeAdapter::$executedCommands[0]);

        $this->assertStringContainsString("JAILBASE='/srv/environments/" . $environment->id . "'", $deleteCommands);
        $this->assertStringContainsString("JAILUSER='usr7'", $deleteCommands);
        $this->assertStringContainsString('userdel "$JAILUSER"', $deleteCommands);
        $this->assertStringContainsString('groupdel "$JAILUSER"', $deleteCommands);
        $this->assertStringContainsString('rm -rf -- "$JAILBASE"', $deleteCommands);

        $auditLog = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('environment_id', $environment->id)
            ->where('action', 'environment "Delete Environment Test" deleted from node "Delete Environment Test Node"')
            ->firstOrFail();

        $this->assertSame($node->id, $auditLog->context['node_id']);
        $this->assertSame('usr7', $auditLog->context['unix_name']);
        $this->assertSame(10007, $auditLog->context['guid']);
        $this->assertArrayNotHasKey('tenant_id', $auditLog->context);
        $this->assertArrayNotHasKey('environment_id', $auditLog->context);
    }

    public function test_retry_does_not_provision_an_already_attached_environment_again(): void
    {
        $tenant = Tenant::query()->firstOrFail();
        $plan = Plan::query()->firstOrFail();
        $node = Node::query()->create([
            'adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Idempotent Environment Test Node',
            'hostname' => 'idempotent-environment-test-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));

        $environment = Environment::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'name' => 'Idempotent Environment Test',
        ]);

        CreateEnvironment::dispatchSync($environment->refresh(), $node);
        $commandCount = count(CreateEnvironmentFakeAdapter::$executedCommands);

        CreateEnvironment::dispatchSync($environment->refresh(), $node);

        $this->assertSame($commandCount, count(CreateEnvironmentFakeAdapter::$executedCommands));
        $this->assertSame(1, DB::table('node_environments')
            ->where('environment_id', $environment->id)
            ->where('node_id', $node->id)
            ->count());
    }

    public function test_failed_jail_creation_cleans_remote_artifacts(): void
    {
        CreateEnvironmentFakeAdapter::$failJailCreation = true;

        $tenant = Tenant::query()->firstOrFail();
        $plan = Plan::query()->firstOrFail();
        $node = Node::query()->create([
            'adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Failed Environment Test Node',
            'hostname' => 'failed-environment-test-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));

        $environment = Environment::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'name' => 'Failed Environment Test',
        ]);

        try {
            CreateEnvironment::dispatchSync($environment->refresh(), $node);
            $this->fail('The jail creation should fail in this test.');
        } catch (NodeException $exception) {
            $this->assertSame('Unable to create jail.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('node_environments', [
            'environment_id' => $environment->id,
            'node_id' => $node->id,
        ]);
        $this->assertContains('/tmp/createhome-' . $environment->id . '.sh', CreateEnvironmentFakeAdapter::$deletedFiles);

        $cleanupCommands = implode(PHP_EOL, end(CreateEnvironmentFakeAdapter::$executedCommands));
        $this->assertStringContainsString('rm -rf -- "$JAILBASE"', $cleanupCommands);
    }
}

class CreateEnvironmentFakeAdapter extends Adapter
{
    public static string $name = 'create-environment-fake-adapter';

    public static int $resolvedGuid = 10005;

    public static string $guidResolutionCommand = '';

    public static string $uploadedScript = '';

    public static bool $failJailCreation = false;

    public static array $deletedFiles = [];

    public static array $executedCommands = [];

    public static function reset(): void
    {
        self::$resolvedGuid = 10005;
        self::$guidResolutionCommand = '';
        self::$uploadedScript = '';
        self::$failJailCreation = false;
        self::$deletedFiles = [];
        self::$executedCommands = [];
    }

    public function exec(string|array $command): bool|string
    {
        $commands = (array)$command;
        self::$executedCommands[] = $commands;

        if (str_starts_with($commands[0] ?? '', 'candidate=')) {
            self::$guidResolutionCommand = implode(PHP_EOL, $commands);

            return (string)self::$resolvedGuid;
        }

        $commandString = implode(PHP_EOL, $commands);
        if (self::$failJailCreation && str_contains($commandString, 'chmod +x') && str_contains($commandString, 'createhome-')) {
            return false;
        }

        return '';
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function storagePut(string $remote, string $data): bool
    {
        self::$uploadedScript = $data;

        return true;
    }

    public function storageGet(string $remote, bool|string $local = false): bool|string
    {
        return '';
    }

    public function storageDelete(string $remote): bool
    {
        self::$deletedFiles[] = $remote;

        return true;
    }

    public function storageExists(string $remote): bool
    {
        return $remote === '/srv/environments';
    }

    public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool
    {
        return true;
    }
}
