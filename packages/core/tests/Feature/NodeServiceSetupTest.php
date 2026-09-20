<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Platform\NodePlatform;
use Froxlor\Core\Services\Node\Platform\PlatformResolver;
use Froxlor\Core\Services\Node\Setup\AdapterNodeServiceExecutor;
use Froxlor\Core\Services\Node\Setup\NodeServiceContext;
use Froxlor\Core\Services\Node\Setup\NodeServicePlanner;
use Froxlor\Core\Services\Node\Setup\NodeServiceProvider;
use Froxlor\Core\Services\Node\Setup\NodeServiceRegistry;
use Froxlor\Core\Services\Node\Setup\NodeSetupScript;
use Froxlor\Core\Services\Node\Setup\Providers\BaseSystemProvider;
use Froxlor\Core\Services\Node\Setup\ServiceOperation;
use Froxlor\Core\Services\Node\Setup\ServicePlan;
use Froxlor\Core\Services\Node\Setup\ServiceSettings;
use Froxlor\Core\Services\Node\Setup\SettingDefinition;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class NodeServiceSetupTest extends TestCase
{
    public function test_core_registers_only_the_base_system_provider(): void
    {
        $registry = app(NodeServiceRegistry::class);
        $this->assertSame($registry, app(NodeServiceRegistry::class));
        $this->assertInstanceOf(BaseSystemProvider::class, $registry->get('froxlor/core:base-system'));
        $plan = (new NodeServicePlanner($registry))->plan($this->node(), ['base-system' => 'froxlor/core:base-system']);
        $operations = $plan->services['froxlor/core:base-system']['plan']->operations();
        $this->assertCount(1, $operations);
        $packages = $operations[0]->payload()['packages'];
        foreach (['sudo', 'ca-certificates', 'logrotate', 'curl', 'dnsutils', 'iproute2', 'iputils-ping', 'procps', 'lsof', 'less', 'jq'] as $package) {
            $this->assertContains($package, $packages);
        }
        foreach (['nginx', 'apache2', 'postfix', 'jailkit', 'proftpd'] as $package) {
            $this->assertNotContains($package, $packages);
        }
    }

    public function test_registration_is_idempotent_and_rejects_key_takeover(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider);
        $registry->register(new SetupFixtureProvider);
        $this->assertCount(1, $registry->available());
        $changed = new SetupFixtureProvider;
        $changed->version = '2';
        $this->expectException(LogicException::class);
        $registry->register($changed);
    }

    public function test_exact_platform_support_is_required(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider);
        $node = $this->node('ubuntu', '24.04');
        $this->assertSame([], $registry->available($node->platform()));
        $this->expectException(InvalidArgumentException::class);
        (new NodeServicePlanner($registry))->plan($node, ['fixture' => 'tests/node-setup:fixture']);
    }

    public function test_unknown_platform_does_not_fall_back_to_debian(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(NodeServicePlanner::class)->plan($this->node('debian', '12'), ['base-system' => 'froxlor/core:base-system']);
    }

    public function test_dependency_order_is_deterministic(): void
    {
        $registry = new NodeServiceRegistry;
        $dependency = new SetupFixtureProvider('dependency');
        $dependent = new SetupFixtureProvider('dependent', ['dependency']);
        $registry->register($dependent);
        $registry->register($dependency);
        $planner = new NodeServicePlanner($registry);
        $selection = ['dependent' => $dependent->key(), 'dependency' => $dependency->key()];
        $first = $planner->plan($this->node(), $selection);
        $this->assertSame([$dependency->key(), $dependent->key()], array_keys($first->services));
        $this->assertSame($first->fingerprint(), $planner->plan($this->node(), array_reverse($selection, true))->fingerprint());
    }

    public function test_missing_dependency_is_not_implicitly_installed(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider('fixture', ['missing']));
        $this->expectException(LogicException::class);
        (new NodeServicePlanner($registry))->plan($this->node(), ['fixture' => 'tests/node-setup:fixture']);
    }

    public function test_dependency_cycles_are_rejected(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider('first', ['second']));
        $registry->register(new SetupFixtureProvider('second', ['first']));
        $this->expectException(LogicException::class);
        (new NodeServicePlanner($registry))->plan($this->node(), [
            'first' => 'tests/node-setup:first', 'second' => 'tests/node-setup:second',
        ]);
    }

    public function test_conflicting_providers_are_rejected(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider('first', conflicts: ['second']));
        $registry->register(new SetupFixtureProvider('second'));
        $this->expectException(LogicException::class);
        (new NodeServicePlanner($registry))->plan($this->node(), [
            'first' => 'tests/node-setup:first', 'second' => 'tests/node-setup:second',
        ]);
    }

    public function test_two_providers_cannot_own_the_same_config(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider('first', config: true));
        $registry->register(new SetupFixtureProvider('second', config: true));
        $this->expectException(LogicException::class);
        (new NodeServicePlanner($registry))->plan($this->node(), [
            'first' => 'tests/node-setup:first', 'second' => 'tests/node-setup:second',
        ]);
    }

    public function test_provider_settings_resolve_defaults_node_values_and_validated_overrides(): void
    {
        $provider = new SetupFixtureProvider;
        $node = $this->node();
        $this->assertSame(4, ServiceSettings::resolve($node, $provider)->integer('workers'));
        $node->settingValues[ServiceSettings::path($provider, 'workers')] = '12';
        $this->assertSame(12, ServiceSettings::resolve($node, $provider)->integer('workers'));
        $this->assertSame(8, ServiceSettings::resolve($node, $provider, ['workers' => 8])->integer('workers'));
        $this->expectException(InvalidArgumentException::class);
        ServiceSettings::resolve($node, $provider, ['workers' => 100000]);
    }

    public function test_unknown_setting_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceSettings::resolve($this->node(), new SetupFixtureProvider, ['command' => 'id']);
    }

    public function test_setting_accessor_rejects_type_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceSettings::resolve($this->node(), new SetupFixtureProvider)->boolean('workers');
    }

    public function test_boolean_and_choice_validation_are_strict(): void
    {
        $this->assertFalse(SettingDefinition::boolean(true)->normalize('0'));
        $this->assertTrue(SettingDefinition::boolean(false)->normalize('1'));
        $this->assertSame('daily', SettingDefinition::choice('daily', ['daily', 'weekly'])->normalize('daily'));
        $this->expectException(InvalidArgumentException::class);
        SettingDefinition::boolean(false)->normalize('false; touch /tmp/unwanted');
    }

    public function test_plan_is_immutable_and_preview_does_not_leak_contents_or_arguments(): void
    {
        $empty = ServicePlan::make();
        $plan = $empty->config('/etc/example.conf', 'private-value')
            ->validateCommand(['/usr/bin/test', 'private-value'])
            ->activateService('example')->healthCheck(['/usr/bin/true']);
        $plan->assertValid();
        $this->assertSame([], $empty->operations());
        $this->assertStringNotContainsString('private-value', json_encode($plan->toArray()));
        $this->assertNotSame($empty->fingerprint(), $plan->fingerprint());
    }

    public function test_unvalidated_configuration_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        ServicePlan::make()->config('/etc/example.conf', 'value')->assertValid();
    }

    #[DataProvider('unsafePaths')]
    public function test_unsafe_config_targets_are_rejected(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceOperation::config($path, 'value');
    }

    public static function unsafePaths(): array
    {
        return [['/'], ['/tmp/example'], ['/etc/../root/key'], ['/etc//example'], ['/etc/example/'], ['/etc/test;id']];
    }

    public function test_package_arguments_cannot_be_options_or_shell_commands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServicePlan::make()->ensurePackages(['--allow-unauthenticated']);
    }

    public function test_compiled_script_parses_without_executing_it(): void
    {
        $registry = new NodeServiceRegistry;
        $registry->register(new SetupFixtureProvider(config: true));
        $plan = (new NodeServicePlanner($registry))->plan($this->node(), ['fixture' => 'tests/node-setup:fixture']);
        $script = (new NodeSetupScript)->compile($plan, '01K00000000000000000000000');
        $process = new Process(['/bin/bash', '-n']);
        $process->setInput($script);
        $process->run();
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('flock -w 300', $script);
        $this->assertStringContainsString('recovery-required', $script);
        $this->assertStringNotContainsString('private-value', $script);
    }

    public function test_executor_rejects_wrong_node_before_accessing_an_adapter(): void
    {
        Gate::before(fn ($user = null) => true);
        $node = $this->node();
        $plan = app(NodeServicePlanner::class)->plan($node, ['base-system' => 'froxlor/core:base-system']);
        $node->id = 'different-node';
        $node->exists = true;
        $this->expectException(LogicException::class);
        app(AdapterNodeServiceExecutor::class)->apply($node, $plan);
    }

    private function node(string $os = 'debian', string $version = '13'): SetupFixtureNode
    {
        $node = new SetupFixtureNode;
        $node->id = '01K00000000000000000000000';
        $node->detectedPlatform = (new PlatformResolver)->fromOsRelease(['id' => $os, 'version_id' => $version]);

        return $node;
    }
}

class SetupFixtureNode extends Node
{
    public array $settingValues = [];

    public NodePlatform $detectedPlatform;

    public function platform(): NodePlatform
    {
        return $this->detectedPlatform;
    }

    public function getSetting(string $settings_path, mixed $default = null): mixed
    {
        return $this->settingValues[$settings_path] ?? $default;
    }
}

class SetupFixtureProvider implements NodeServiceProvider
{
    public string $version = '1';

    public function __construct(private string $serviceRole = 'fixture', private array $dependencies = [], private array $conflicts = [], private bool $config = false) {}

    public function key(): string
    {
        return 'tests/node-setup:'.$this->serviceRole;
    }

    public function role(): string
    {
        return $this->serviceRole;
    }

    public function package(): string
    {
        return 'tests/node-setup';
    }

    public function revision(): string
    {
        return $this->version;
    }

    public function platforms(): array
    {
        return ['debian@13'];
    }

    public function requires(): array
    {
        return $this->dependencies;
    }

    public function conflicts(): array
    {
        return $this->conflicts;
    }

    public function settings(): array
    {
        return ['workers' => SettingDefinition::integer(default: 4, min: 1, max: 32)];
    }

    public function plan(NodeServiceContext $context): ServicePlan
    {
        $plan = ServicePlan::make()->ensurePackages(['ca-certificates']);

        return $this->config
            ? $plan->config('/etc/example.conf', 'private-value')
                ->validateCommand(['/usr/bin/true'])
                ->activateService('example')->healthCheck(['/usr/bin/true'])
            : $plan;
    }
}
