<?php

namespace Tests\Feature;

use Froxlor\Core\Jobs\Node\ExploreNode;
use Froxlor\Core\Jobs\Node\SetupNode;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Setup\NodeServiceExecutor;
use Froxlor\Core\Services\Node\Setup\NodeSetupPlan;
use Froxlor\Core\Services\Node\Setup\NodeSetupResult;
use Froxlor\Core\Services\Node\Setup\NodeSetupService;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

class NodeSetupLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    private bool $allowed = true;

    private int $executions = 0;

    private bool $executionFails = false;

    private bool $usePolicies = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32))]);
        $this->actor = User::query()->create([
            'email' => 'node-setup-'.str()->ulid().'@example.test', 'password' => 'test-password',
        ]);
        Gate::before(fn (User $user) => $this->usePolicies ? null : ($user->id === $this->actor->id && $this->allowed));
        Bus::fake();
        if (! in_array(SetupLifecycleAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(SetupLifecycleAdapter::class);
        }
        SetupLifecycleAdapter::$connected = true;
        $this->app->instance(NodeServiceExecutor::class, new class($this->apply(...)) implements NodeServiceExecutor
        {
            public function __construct(private \Closure $apply) {}

            public function apply(Node $node, NodeSetupPlan $plan): NodeSetupResult
            {
                return ($this->apply)($node, $plan);
            }
        });
    }

    public function test_initial_exploration_queues_one_setup_with_the_original_actor(): void
    {
        $node = $this->node();
        (new ExploreNode($node, true, $this->actor->id))->handle();
        $node->refresh();
        $this->assertSame('pending', $node->setup_status);
        $this->assertSame($this->actor->id, $node->setup_requested_by);
        Bus::assertDispatched(SetupNode::class, fn (SetupNode $job) => $job->nodeId === $node->id && $job->requestId === $node->setup_request_id);
        (new ExploreNode($node, true, $this->actor->id))->handle();
        Bus::assertDispatchedTimes(SetupNode::class, 1);
        $this->assertSame(1, $node->nodeInterfaces()->where('bind_addr', '192.0.2.10')->count());
    }

    public function test_regular_exploration_never_queues_setup(): void
    {
        $node = $this->node();
        (new ExploreNode($node))->handle();
        $this->assertNull($node->refresh()->setup_status);
        Bus::assertNotDispatched(SetupNode::class);
    }

    public function test_failed_exploration_never_queues_setup(): void
    {
        SetupLifecycleAdapter::$connected = false;
        try {
            (new ExploreNode($this->node(), true, $this->actor->id))->handle();
            $this->fail('Expected connection failure.');
        } catch (\Exception) {
            Bus::assertNotDispatched(SetupNode::class);
        }
    }

    public function test_missing_initial_actor_fails_closed_and_is_visible(): void
    {
        $node = $this->node();
        (new ExploreNode($node, true))->handle();
        $this->assertSame('failed', $node->refresh()->setup_status);
        Bus::assertNotDispatched(SetupNode::class);
    }

    public function test_job_runs_as_original_user_and_restores_worker_context(): void
    {
        Setting::set('auditlog.enabled', true, source: 'froxlor/core');
        Setting::set('auditlog.severity', 7, source: 'froxlor/core');
        $node = $this->request();
        $priorUser = User::query()->create(['email' => 'prior-'.str()->ulid().'@example.test', 'password' => 'test-password']);
        auth()->guard()->setUser($priorUser);
        $resolver = fn () => $priorUser;
        request()->setUserResolver($resolver);

        $job = unserialize(serialize(new SetupNode($node->id, $node->setup_request_id)));
        $job->handle(app(NodeSetupService::class));
        $this->assertSame('succeeded', $node->refresh()->setup_status);
        $this->assertNotNull($node->setup_started_at);
        $this->assertNotNull($node->setup_finished_at);
        $this->assertSame($node->setup_request_id, $node->setup_run_id);
        $this->assertSame($priorUser->id, auth()->id());
        $this->assertSame($resolver, request()->getUserResolver());
        $audit = AuditLog::query()->where('action', 'setup fixture executed')->firstOrFail();
        $this->assertSame($this->actor->id, $audit->auditable_id);
    }

    public function test_duplicate_job_delivery_does_not_repeat_execution(): void
    {
        $node = $this->request();
        $job = new SetupNode($node->id, $node->setup_request_id);
        $job->handle(app(NodeSetupService::class));
        $job->handle(app(NodeSetupService::class));
        $this->assertSame(1, $this->executions);
    }

    public function test_revoked_permission_prevents_queued_execution(): void
    {
        $node = $this->request();
        $this->allowed = false;
        $this->expectFailedJob($node);
        $this->assertSame(0, $this->executions);
    }

    public function test_deleted_actor_prevents_queued_execution(): void
    {
        $node = $this->request();
        $this->actor->delete();
        $this->expectFailedJob($node);
        $this->assertSame(0, $this->executions);
    }

    public function test_changed_target_invalidates_queued_plan(): void
    {
        $node = $this->request();
        $node->forceFill(['hostname' => 'different.example.test'])->saveQuietly();
        $this->expectFailedJob($node);
        $this->assertSame(0, $this->executions);
    }

    public function test_execution_failure_is_sanitized_and_restores_guest_context(): void
    {
        $node = $this->request();
        $this->executionFails = true;
        $this->expectFailedJob($node);
        $this->assertStringNotContainsString('SECRET', $node->refresh()->setup_error);
        $this->assertNull(auth()->user());
        $this->assertNull(request()->user());
    }

    public function test_api_exposes_status_and_rejects_duplicate_requests(): void
    {
        $node = $this->node();
        $this->actingAs($this->actor, 'sanctum')->getJson('/api/nodes/'.$node->id.'/setup')
            ->assertOk()->assertJsonPath('data.status', null);
        $this->postJson('/api/nodes/'.$node->id.'/setup')->assertStatus(202)->assertJsonPath('data.status', 'pending');
        $this->postJson('/api/nodes/'.$node->id.'/setup')->assertStatus(409);
        Bus::assertDispatchedTimes(SetupNode::class, 1);
    }

    public function test_api_rejects_unauthenticated_and_unauthorized_requests(): void
    {
        $node = $this->node();
        $this->postJson('/api/nodes/'.$node->id.'/setup')->assertUnauthorized();
        $this->allowed = false;
        $this->actingAs($this->actor, 'sanctum')->postJson('/api/nodes/'.$node->id.'/setup')->assertForbidden();
        Bus::assertNotDispatched(SetupNode::class);
    }

    public function test_tenant_setup_uses_ownership_policy_and_rejects_inherited_nodes(): void
    {
        $this->usePolicies = true;
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $owned = $this->node();
        $owned->forceFill(['tenant_id' => $tenant->id])->saveQuietly();
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/'.$tenant->id.'/nodes/'.$owned->id.'/setup')->assertStatus(202);
        $this->getJson('/api/tenants/'.$tenant->id.'/nodes/'.$owned->id.'/setup')
            ->assertOk()->assertJsonPath('data.requested_by', $user->id);

        $inherited = $this->node();
        $inherited->tenants()->attach($tenant, ['inheritable' => true]);
        $this->postJson('/api/tenants/'.$tenant->id.'/nodes/'.$inherited->id.'/setup')->assertForbidden();
        Bus::assertDispatchedTimes(SetupNode::class, 1);
    }

    public function test_unsupported_platform_is_rejected_without_queueing(): void
    {
        $node = $this->node();
        $node->forceFill(['properties' => ['os' => ['id' => 'unknown', 'version_id' => '1']]])->saveQuietly();
        $this->actingAs($this->actor, 'sanctum')->postJson('/api/nodes/'.$node->id.'/setup')
            ->assertUnprocessable()->assertJsonValidationErrors('node');
        Bus::assertNotDispatched(SetupNode::class);
    }

    public function test_broker_failure_leaves_failed_status_and_does_not_leak_output(): void
    {
        $node = $this->node();
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('SECRET broker credentials'));
        try {
            app(NodeSetupService::class)->request($node, $this->actor);
            $this->fail('Expected broker failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('SECRET', $exception->getMessage());
            $this->assertSame('failed', $node->refresh()->setup_status);
        }
    }

    public function test_retry_gets_new_request_and_old_failure_hook_cannot_overwrite_it(): void
    {
        $node = $this->request();
        $old = new SetupNode($node->id, $node->setup_request_id);
        $old->failed(new RuntimeException('timeout SECRET'));
        $this->assertSame('failed', $node->refresh()->setup_status);
        $this->actingAs($this->actor, 'sanctum')->postJson('/api/nodes/'.$node->id.'/setup')->assertStatus(202);
        $this->assertNotSame($old->requestId, $node->refresh()->setup_request_id);
        $old->failed(new RuntimeException('old delivery'));
        $this->assertSame('pending', $node->refresh()->setup_status);
    }

    public function test_transaction_rollback_does_not_dispatch_job(): void
    {
        $node = $this->node();
        DB::beginTransaction();
        app(NodeSetupService::class)->request($node, $this->actor);
        Bus::assertNotDispatched(SetupNode::class);
        DB::rollBack();
        $this->assertNull($node->refresh()->setup_status);
        Bus::assertNotDispatched(SetupNode::class);
    }

    public function test_explicit_retry_can_replace_an_abandoned_request_after_30_minutes(): void
    {
        $node = $this->request();
        $old = new SetupNode($node->id, $node->setup_request_id);
        $node->forceFill(['setup_status' => 'running', 'setup_started_at' => now()->subMinutes(31)])->saveQuietly();
        $new = app(NodeSetupService::class)->request($node, $this->actor);
        $this->assertNotSame($old->requestId, $new->setup_request_id);
        $old->handle(app(NodeSetupService::class));
        $this->assertSame(0, $this->executions);
        $this->assertSame('pending', $new->refresh()->setup_status);
    }

    public function test_cli_requires_explicit_user_and_queues_authorized_request(): void
    {
        $node = $this->node();
        $this->artisan('core:setup-node', ['node' => $node->id])->assertFailed();
        $this->artisan('core:setup-node', ['node' => $node->id, '--user' => $this->actor->id])->assertSuccessful();
        Bus::assertDispatchedTimes(SetupNode::class, 1);
    }

    public function test_setup_has_a_separate_queue_and_long_enough_retry_window(): void
    {
        $job = new SetupNode('node', 'request');
        $this->assertSame('node-setup', $job->connection);
        $this->assertSame('node-setup', $job->queue);
        $this->assertGreaterThan($job->timeout, config('queue.connections.node-setup.retry_after'));
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame(1, $job->tries);
    }

    private function apply(Node $node, NodeSetupPlan $plan): NodeSetupResult
    {
        $this->executions++;
        $this->assertSame($this->actor->id, auth()->id());
        $this->assertSame($this->actor->id, request()->user()->id);
        if ($this->executionFails) {
            throw new RuntimeException('SECRET connection output');
        }
        Audit::info('setup fixture executed', $node->tenant, context: ['node_id' => $node->id]);

        return new NodeSetupResult($node->setup_request_id, $plan->fingerprint());
    }

    private function expectFailedJob(Node $node): void
    {
        try {
            (new SetupNode($node->id, $node->setup_request_id))->handle(app(NodeSetupService::class));
            $this->fail('Expected job failure.');
        } catch (RuntimeException $exception) {
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('SECRET', $exception->getMessage());
            $this->assertSame('failed', $node->refresh()->setup_status);
        }
    }

    private function request(): Node
    {
        return app(NodeSetupService::class)->request($this->node(), $this->actor);
    }

    private function node(): Node
    {
        return Node::withoutEvents(fn () => Node::query()->create([
            'name' => 'Setup lifecycle test', 'hostname' => 'setup.example.test', 'username' => 'root',
            'adapter' => SetupLifecycleAdapter::class, 'sudo' => false,
            'properties' => ['os' => ['id' => 'debian', 'version_id' => '13']],
        ]));
    }
}

class SetupLifecycleAdapter extends Adapter
{
    public static string $name = 'setup-lifecycle-test';

    public static bool $connected = true;

    public function isConnected(): bool
    {
        return self::$connected;
    }

    public function exec(string|array $command): bool|string
    {
        $command = implode("\n", (array) $command);

        return match (true) {
            $command === 'cat /etc/os-release' => "ID=debian\nVERSION_ID=13\nVERSION_CODENAME=trixie\nPRETTY_NAME=Debian",
            $command === 'uname -r' => '6.1',
            $command === 'nproc' => '4',
            $command === 'cat /proc/stat' => 'cpu  10 0 10 100 0 0 0',
            $command === 'cat /proc/meminfo' => "MemTotal: 1000 kB\nMemFree: 500 kB\nMemAvailable: 600 kB",
            str_starts_with($command, 'df ') => '/dev/vda 1000 100 900 /',
            str_starts_with($command, 'awk ') => '9999',
            $command === 'hostname -I' => '192.0.2.10',
            default => throw new RuntimeException('Unexpected transport invocation in exploration fixture'),
        };
    }

    public function storagePut(string $remote, string $data): bool
    {
        throw new RuntimeException('Unexpected I/O');
    }

    public function storageGet(string $remote, bool|string $local = false): bool|string
    {
        throw new RuntimeException('Unexpected I/O');
    }

    public function storageDelete(string $remote): bool
    {
        throw new RuntimeException('Unexpected I/O');
    }

    public function storageExists(string $remote): bool
    {
        throw new RuntimeException('Unexpected I/O');
    }

    public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool
    {
        throw new RuntimeException('Unexpected I/O');
    }
}
