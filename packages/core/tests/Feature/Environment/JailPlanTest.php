<?php

namespace Tests\Feature\Environment;

use Froxlor\Core\Services\Environment\Jail\JailContext;
use Froxlor\Core\Services\Environment\Jail\JailPlan;
use Froxlor\Core\Services\Environment\Jail\JailProvider;
use Froxlor\Core\Services\Environment\Jail\JailRegistry;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class JailPlanTest extends TestCase
{
    private function context(): JailContext
    {
        return new JailContext('01K00000000000000000000000', 'tenant', 'node', '/srv/environments/01K00000000000000000000000', 'usr1', 10001);
    }

    private function provider(string $key, JailPlan $plan): JailProvider
    {
        return new class($key, $plan) implements JailProvider
        {
            public function __construct(private string $key, private JailPlan $definition) {}

            public function key(): string
            {
                return $this->key;
            }

            public function package(): string
            {
                return 'froxlor/test';
            }

            public function settings(): array
            {
                return [];
            }

            public function plan(JailContext $context, \Froxlor\Core\Services\Environment\Jail\EnvironmentSettings $settings): JailPlan
            {
                return $this->definition;
            }
        };
    }

    public function test_providers_union_shared_binaries_and_users_deterministically(): void
    {
        $registry = new JailRegistry;
        $plan = (new JailPlan)->binary('/usr/bin/php')->user('worker', 20001, 20001);
        $registry->register($this->provider('froxlor/web:php', $plan));
        $registry->register($this->provider('froxlor/jobs:php', $plan));
        $result = $registry->plan($this->context(), new \Froxlor\Core\Models\Environment);
        $this->assertSame(['/usr/bin/php'], $result['binaries']);
        $this->assertCount(1, $result['users']);
        $this->assertCount(2, $result['providers']);
    }

    public function test_conflicting_package_user_definitions_fail_before_execution(): void
    {
        $registry = new JailRegistry;
        $registry->register($this->provider('froxlor/web:worker', (new JailPlan)->user('worker', 20001, 20001)));
        $registry->register($this->provider('froxlor/jobs:worker', (new JailPlan)->user('worker', 20002, 20002)));
        $this->expectException(LogicException::class);
        $registry->plan($this->context(), new \Froxlor\Core\Models\Environment);
    }

    public function test_primary_identity_cannot_be_overridden(): void
    {
        $registry = new JailRegistry;
        $registry->register($this->provider('froxlor/web:worker', (new JailPlan)->user('other', 10001, 20001)));
        $this->expectException(LogicException::class);
        $registry->plan($this->context(), new \Froxlor\Core\Models\Environment);
    }

    public function test_unsafe_paths_are_rejected(): void
    {
        foreach (['/bin/../etc/passwd', '/usr/bin/php;id', '/usr/bin/..', 'php', '/tmp/php'] as $path) {
            try {
                (new JailPlan)->binary($path);
                $this->fail('Unsafe binary path accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        new JailContext('01K00000000000000000000000', 'tenant', 'node', '/srv/../01K00000000000000000000000', 'usr1', 10001);
    }

    public function test_root_user_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new JailPlan)->user('root', 0, 0);
    }

    public function test_duplicate_registry_keys_fail_closed(): void
    {
        $registry = new JailRegistry;
        $registry->register($this->provider('froxlor/web:php', new JailPlan));
        $this->expectException(LogicException::class);
        $registry->register($this->provider('froxlor/web:php', new JailPlan));
    }

    public function test_declarative_files_directories_and_environment_are_exported(): void
    {
        $plan = (new JailPlan)
            ->directory('/etc/php', 0750)
            ->file('/etc/php/php.ini', "memory_limit=128M\n", 0640)
            ->environment('APP_ENV', 'production');

        $this->assertSame([
            'binaries' => [],
            'users' => [],
            'files' => ['/etc/php/php.ini' => ['content' => "memory_limit=128M\n", 'mode' => 0640]],
            'directories' => ['/etc/php' => 0750],
            'environment' => ['APP_ENV' => 'production'],
        ], $plan->toArray());
    }
}
