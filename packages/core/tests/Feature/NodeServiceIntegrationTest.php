<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Setup\NodeServiceContext;
use Froxlor\Core\Services\Node\Setup\NodeServiceExecutor;
use Froxlor\Core\Services\Node\Setup\NodeServicePlanner;
use Froxlor\Core\Services\Node\Setup\NodeServiceProvider;
use Froxlor\Core\Services\Node\Setup\NodeServiceRegistry;
use Froxlor\Core\Services\Node\Setup\NodeSetupPlan;
use Froxlor\Core\Services\Node\Setup\ServicePlan;
use Froxlor\Core\Services\Node\Setup\ServiceSettings;
use Froxlor\Core\Services\Node\Setup\SettingDefinition;
use Froxlor\Core\Support\Setting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class NodeServiceIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32))]);
        SetupIntegrationAdapter::$calls = [];
        SetupIntegrationAdapter::$succeeds = true;
        if (! in_array(SetupIntegrationAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(SetupIntegrationAdapter::class);
        }
        app(NodeServiceRegistry::class)->register(new SetupIntegrationProvider);
    }

    public function test_settings_keep_external_package_ownership_and_node_precedence(): void
    {
        $provider = new SetupIntegrationProvider;
        $node = $this->node();
        $path = ServiceSettings::path($provider, 'workers');
        Setting::add($path, 2, source: $provider->package());
        $this->assertSame(2, ServiceSettings::resolve($node, $provider)->integer('workers'));
        Setting::setValueForType(Node::class, $path, 6, source: $provider->package());
        $this->assertSame(6, ServiceSettings::resolve($node, $provider)->integer('workers'));
        ServiceSettings::store($node, $provider, ['workers' => 8, 'enabled' => false]);
        $resolved = ServiceSettings::resolve($node, $provider);
        $this->assertSame(8, $resolved->integer('workers'));
        $this->assertFalse($resolved->boolean('enabled'));
        $this->assertDatabaseHas('settings', [
            'category' => 'services', 'key' => $provider->key().'.workers',
            'settingable_id' => $node->id, 'owner_package' => $provider->package(),
        ]);
    }

    public function test_invalid_setting_batch_does_not_write_partial_values(): void
    {
        $node = $this->node();
        try {
            ServiceSettings::store($node, new SetupIntegrationProvider, ['workers' => 8, 'enabled' => 'invalid']);
            $this->fail('Expected validation failure.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, SettingModel::query()->where('settingable_id', $node->id)->count());
        }
    }

    public function test_executor_requires_authorization_before_transport(): void
    {
        Gate::before(fn ($user = null) => false);
        $node = $this->node();
        $plan = $this->plan($node);
        try {
            app(NodeServiceExecutor::class)->apply($node, $plan);
            $this->fail('Expected authorization failure.');
        } catch (AuthorizationException) {
            $this->assertSame([], SetupIntegrationAdapter::$calls);
        }
    }

    public function test_executor_rejects_changed_settings_before_transport(): void
    {
        Gate::before(fn ($user = null) => true);
        $node = $this->node();
        $plan = $this->plan($node);
        ServiceSettings::store($node, new SetupIntegrationProvider, ['workers' => 8]);
        try {
            app(NodeServiceExecutor::class)->apply($node, $plan);
            $this->fail('Expected stale-plan rejection.');
        } catch (LogicException) {
            $this->assertSame([], SetupIntegrationAdapter::$calls);
        }
    }

    public function test_executor_accepts_only_the_success_marker_and_preserves_fingerprint(): void
    {
        Gate::before(fn ($user = null) => true);
        $node = $this->node();
        $plan = $this->plan($node);
        $result = app(NodeServiceExecutor::class)->apply($node, $plan);
        $this->assertSame($plan->fingerprint(), $result->fingerprint);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $result->runId);
        $this->assertCount(1, SetupIntegrationAdapter::$calls);
    }

    public function test_executor_rejects_changed_connection_identity(): void
    {
        Gate::before(fn ($user = null) => true);
        $node = $this->node();
        $plan = $this->plan($node);
        Node::withoutEvents(fn () => $node->update(['hostname' => 'another-node.invalid']));
        try {
            app(NodeServiceExecutor::class)->apply($node, $plan);
            $this->fail('Expected stale target rejection.');
        } catch (LogicException) {
            $this->assertSame([], SetupIntegrationAdapter::$calls);
        }
    }

    public function test_executor_does_not_treat_arbitrary_adapter_output_as_success(): void
    {
        Gate::before(fn ($user = null) => true);
        SetupIntegrationAdapter::$succeeds = false;
        try {
            $node = $this->node();
            app(NodeServiceExecutor::class)->apply($node, $this->plan($node));
            $this->fail('Expected execution failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('SECRET-OUTPUT', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    private function plan(Node $node): NodeSetupPlan
    {
        return app(NodeServicePlanner::class)->plan($node, ['integration' => 'tests/node-integration:fixture']);
    }

    private function node(): Node
    {
        return Node::withoutEvents(fn () => Node::query()->create([
            'name' => 'Setup integration fixture', 'hostname' => 'setup.invalid',
            'username' => 'root', 'sudo' => false, 'adapter' => SetupIntegrationAdapter::class,
            'properties' => ['os' => ['id' => 'debian', 'version_id' => '13']],
        ]));
    }
}

class SetupIntegrationProvider implements NodeServiceProvider
{
    public function key(): string
    {
        return 'tests/node-integration:fixture';
    }

    public function role(): string
    {
        return 'integration';
    }

    public function package(): string
    {
        return 'tests/node-integration';
    }

    public function revision(): string
    {
        return '1';
    }

    public function platforms(): array
    {
        return ['debian@13'];
    }

    public function requires(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return [];
    }

    public function settings(): array
    {
        return [
            'workers' => SettingDefinition::integer(4, 1, 32),
            'enabled' => SettingDefinition::boolean(true),
        ];
    }

    public function plan(NodeServiceContext $context): ServicePlan
    {
        return ServicePlan::make()->config('/etc/fixture.conf', (string) $context->settings->integer('workers'))
            ->validateCommand(['/usr/bin/true']);
    }
}

/** Never executes the command. Parses the opaque script only to return its run marker. */
class SetupIntegrationAdapter extends Adapter
{
    public static string $name = 'setup-integration';

    public static array $calls = [];

    public static bool $succeeds = true;

    public function exec(string|array $command): bool|string
    {
        self::$calls[] = $command;
        if (! self::$succeeds) {
            return 'SECRET-OUTPUT';
        }
        preg_match("/printf %s '([A-Za-z0-9+\/=]+)'/", implode("\n", (array) $command), $encoded);
        $script = base64_decode($encoded[1], true);
        preg_match("/run='([0-9A-Z]{26})'/", $script, $run);

        return 'FROXLOR_SETUP_OK:'.$run[1];
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function storagePut(string $remote, string $data): bool
    {
        throw new LogicException('Unexpected I/O');
    }

    public function storageGet(string $remote, bool|string $local = false): bool|string
    {
        throw new LogicException('Unexpected I/O');
    }

    public function storageDelete(string $remote): bool
    {
        throw new LogicException('Unexpected I/O');
    }

    public function storageExists(string $remote): bool
    {
        throw new LogicException('Unexpected I/O');
    }

    public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool
    {
        throw new LogicException('Unexpected I/O');
    }
}
