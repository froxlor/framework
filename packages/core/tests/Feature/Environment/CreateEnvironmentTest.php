<?php

namespace Tests\Feature\Environment;

use Froxlor\Core\Jobs\Environment\CreateEnvironment;
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
        $this->assertStringContainsString('JAIL_USER="usr5"', CreateEnvironmentFakeAdapter::$uploadedScript);
        $this->assertStringContainsString('GUID="10005"', CreateEnvironmentFakeAdapter::$uploadedScript);
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
