<?php

namespace Tests\Feature\Environment;

use Froxlor\Core\Jobs\Environment\CreateEnvironment;
use Froxlor\Core\Jobs\Environment\DeleteEnvironment;
use Froxlor\Core\Jobs\Environment\SyncEnvironmentJail;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Environment\Jail\JailReconciler;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateEnvironmentTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        $this->plan = Model::withoutEvents(fn () => Plan::query()->create(['name' => 'Jail test']));
        $this->tenant = Model::withoutEvents(fn () => Tenant::query()->create(['name' => 'Jail test', 'plan_id' => $this->plan->id]));

        CreateEnvironmentFakeAdapter::reset();

        if (! in_array(CreateEnvironmentFakeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(CreateEnvironmentFakeAdapter::class);
        }
    }

    public function test_it_skips_occupied_system_guid_and_persists_the_next_free_guid(): void
    {
        CreateEnvironmentFakeAdapter::$resolvedGuid = 10005;

        $tenant = $this->tenant;
        $plan = $this->plan;
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

        (new CreateEnvironment($environment->refresh(), $node))->handle();

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
        $this->assertStringContainsString("JAILUSER='usr5'", CreateEnvironmentFakeAdapter::$uploadedScript);
        $this->assertStringContainsString("GUID='10005'", CreateEnvironmentFakeAdapter::$uploadedScript);
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
        $tenant = $this->tenant;
        $plan = $this->plan;
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

        $deleteCommands = CreateEnvironmentFakeAdapter::$lastPayload;

        $this->assertSame('/srv/environments/'.$environment->id, $deleteCommands['root']);
        $this->assertSame('usr7', $deleteCommands['user']);
        $this->assertSame('delete', $deleteCommands['operation']);

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
        $tenant = $this->tenant;
        $plan = $this->plan;
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

        (new CreateEnvironment($environment->refresh(), $node))->handle();
        $commandCount = count(CreateEnvironmentFakeAdapter::$executedCommands);

        (new CreateEnvironment($environment->refresh(), $node))->handle();

        $this->assertSame($commandCount + 1, count(CreateEnvironmentFakeAdapter::$executedCommands));
        $this->assertSame(1, CreateEnvironmentFakeAdapter::$creationCount);
        $this->assertSame(1, DB::table('node_environments')
            ->where('environment_id', $environment->id)
            ->where('node_id', $node->id)
            ->count());
    }

    public function test_failed_jail_creation_cleans_remote_artifacts(): void
    {
        CreateEnvironmentFakeAdapter::$failJailCreation = true;

        $tenant = $this->tenant;
        $plan = $this->plan;
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
            (new CreateEnvironment($environment->refresh(), $node))->handle();
            $this->fail('The jail creation should fail in this test.');
        } catch (NodeException $exception) {
            $this->assertSame('Unable to create jail.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('node_environments', [
            'environment_id' => $environment->id,
            'node_id' => $node->id,
        ]);
        $this->assertSame('delete', CreateEnvironmentFakeAdapter::$lastPayload['operation']);
        $this->assertNotEmpty(CreateEnvironmentFakeAdapter::$lastPayload['cleanup_token']);
    }

    public function test_failed_package_reconcile_keeps_jail_and_retries_without_recreation(): void
    {
        $node = Node::query()->create(['adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Jail retry', 'hostname' => 'jail-retry.local', 'username' => 'root', 'sudo' => true]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));
        $environment = Environment::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Retry extension']);
        CreateEnvironmentFakeAdapter::$failReconcile = true;
        try {
            (new CreateEnvironment($environment, $node))->handle();
            $this->fail('Expected package reconciliation failure.');
        } catch (NodeException) {
            $this->assertDatabaseHas('node_environments', ['node_id' => $node->id, 'environment_id' => $environment->id]);
            $this->assertArrayNotHasKey('cleanup_token', CreateEnvironmentFakeAdapter::$lastPayload);
        }
        CreateEnvironmentFakeAdapter::$failReconcile = false;
        (new CreateEnvironment($environment, $node))->handle();
        $this->assertSame(1, CreateEnvironmentFakeAdapter::$creationCount);
        $this->assertNotNull(DB::table('node_environments')->where('environment_id', $environment->id)->value('jail_manifest'));
    }

    public function test_reconcile_uses_persisted_jail_path_after_node_setting_changes(): void
    {
        $node = Node::query()->create(['adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Jail path', 'hostname' => 'jail-path.local', 'username' => 'root', 'sudo' => true]);
        $node->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));
        $environment = Environment::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Stable path']);
        (new CreateEnvironment($environment, $node))->handle();
        $node->setSetting('node.basedir', '/new/location');
        (new SyncEnvironmentJail($environment, $node))->handle(app(JailReconciler::class));
        $this->assertSame('/srv/environments/'.$environment->id, CreateEnvironmentFakeAdapter::$lastPayload['root']);
        DeleteEnvironment::dispatchSync($environment);
        $this->assertSame('/srv/environments/'.$environment->id, CreateEnvironmentFakeAdapter::$lastPayload['root']);
    }

    public function test_delayed_sync_does_not_recreate_a_removed_attachment(): void
    {
        $node = Node::query()->create(['adapter' => CreateEnvironmentFakeAdapter::class,
            'name' => 'Detached jail', 'hostname' => 'detached-jail.local', 'username' => 'root', 'sudo' => true]);
        $environment = Environment::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Detached']);
        (new SyncEnvironmentJail($environment, $node))->handle(app(JailReconciler::class));
        $this->assertSame([], CreateEnvironmentFakeAdapter::$executedCommands);
    }
}

class CreateEnvironmentFakeAdapter extends Adapter
{
    public static string $name = 'create-environment-fake-adapter';

    public static int $resolvedGuid = 10005;

    public static int $creationCount = 0;

    public static array $lastPayload = [];

    public static bool $failReconcile = false;

    public static string $guidResolutionCommand = '';

    public static string $uploadedScript = '';

    public static bool $failJailCreation = false;

    public static array $deletedFiles = [];

    public static array $executedCommands = [];

    public static function reset(): void
    {
        self::$resolvedGuid = 10005;
        self::$creationCount = 0;
        self::$lastPayload = [];
        self::$failReconcile = false;
        self::$guidResolutionCommand = '';
        self::$uploadedScript = '';
        self::$failJailCreation = false;
        self::$deletedFiles = [];
        self::$executedCommands = [];
    }

    public function exec(string|array $command): bool|string
    {
        $commands = (array) $command;
        self::$executedCommands[] = $commands;

        if (str_starts_with($commands[0] ?? '', 'candidate=')) {
            self::$guidResolutionCommand = implode(PHP_EOL, $commands);

            return (string) self::$resolvedGuid;
        }

        $commandString = implode(PHP_EOL, $commands);
        if (preg_match("/printf %s '([A-Za-z0-9+\\/=]+)'/", $commandString, $matches)) {
            $script = base64_decode($matches[1]);
            if (str_contains($script, 'jk_jailuser -j')) {
                self::$uploadedScript = $script;
                self::$creationCount++;

                return self::$failJailCreation ? false : 'FROXLOR_JAIL_CREATED';
            }
            if (preg_match("/python3 - '([A-Za-z0-9+\\/=]+)'/", $commandString, $payload)) {
                self::$lastPayload = json_decode(base64_decode($payload[1]), true);
            }

            return self::$failReconcile && ! isset(self::$lastPayload['operation']) ? false : 'FROXLOR_JAIL_OK';
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
