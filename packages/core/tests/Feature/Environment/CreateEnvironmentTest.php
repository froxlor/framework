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
}

class CreateEnvironmentFakeAdapter extends Adapter
{
    public static string $name = 'create-environment-fake-adapter';

    public static int $resolvedGuid = 10005;

    public static string $guidResolutionCommand = '';

    public static string $uploadedScript = '';

    public static array $executedCommands = [];

    public static function reset(): void
    {
        self::$resolvedGuid = 10005;
        self::$guidResolutionCommand = '';
        self::$uploadedScript = '';
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
