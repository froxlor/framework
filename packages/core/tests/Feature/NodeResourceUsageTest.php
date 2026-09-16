<?php

namespace Tests\Feature;

use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use RuntimeException;
use Tests\Fakes\FakeNodeAdapter;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fakes\BuildsResourceUsageFixtures;

require_once dirname(__DIR__) . '/Fakes/BuildsResourceUsageFixtures.php';

class NodeResourceUsageTest extends TestCase
{
    use DatabaseTransactions, BuildsResourceUsageFixtures;
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildResourceUsageFixtures();

        require_once dirname(__DIR__) . '/Fakes/FakeNodeAdapter.php';

        if (!in_array(FakeNodeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(FakeNodeAdapter::class);
        }
    }

    public function test_tenant_owned_node_creates_and_removes_resource_usage(): void
    {
        $tenant = $this->quotaTenant;
        $user = $this->quotaActor;
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $tenant->update(['plan_id' => $this->quotaPlan->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNode($tenant, 'Usage Test Node');

        $this->assertDatabaseHas('tenant_usage', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'resource_key' => Node::getResourceKey(),
            'resource_id' => $node->id,
        ]);

        $node->delete();

        $this->assertDatabaseMissing('tenant_usage', [
            'tenant_id' => $tenant->id,
            'resource_key' => Node::getResourceKey(),
            'resource_id' => $node->id,
        ]);
    }

    public function test_tenant_owned_node_creation_respects_plan_resource_limit(): void
    {
        $tenant = $this->quotaTenant;
        $user = $this->quotaActor;
        $resource = Resource::query()->where('key', Node::getResourceKey())->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $plan = Plan::query()->create([
            'name' => 'Single Node Limit ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 1]);
        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum');

        $this->createTenantNode($tenant, 'Allowed Node');

        $this->expectException(ResourceLimitException::class);

        $this->createTenantNode($tenant, 'Rejected Node');
    }

    public function test_parent_tenant_user_creating_node_for_subtenant_charges_only_the_owner_with_parent_reservations(): void
    {
        $parentTenant = $this->quotaTenant;
        $subTenant = $this->quotaChild();
        $user = $this->quotaActor;

        $parentTenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $subTenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $parentTenant->update(['plan_id' => $this->quotaPlan->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNode($subTenant, 'Forced Subtenant Node');

        $this->assertDatabaseMissing('tenant_usage', [
            'tenant_id' => $parentTenant->id,
            'user_id' => $user->id,
            'resource_key' => Node::getResourceKey(),
            'resource_id' => $node->id,
        ]);
        $this->assertDatabaseHas('tenant_usage', [
            'tenant_id' => $subTenant->id,
            'user_id' => $user->id,
            'resource_key' => Node::getResourceKey(),
            'resource_id' => $node->id,
        ]);
    }

    public function test_node_with_assigned_environments_cannot_be_deleted_and_keeps_usage(): void
    {
        $tenant = $this->quotaTenant;
        $user = $this->quotaActor;
        $plan = $this->quotaPlan;
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNode($tenant, 'Node With Environment');
        $environment = Environment::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'name' => 'Environment On Node ' . str()->ulid(),
        ]);
        $node->environments()->attach($environment, [
            'unix_name' => $node->latestUnixName,
            'guid' => $node->nextGuid,
            'mode' => 'main',
        ]);

        $this->expectException(RuntimeException::class);

        try {
            $node->delete();
        } finally {
            $this->assertDatabaseHas('nodes', ['id' => $node->id]);
            $this->assertDatabaseHas('tenant_usage', [
                'tenant_id' => $tenant->id,
                'resource_key' => Node::getResourceKey(),
                'resource_id' => $node->id,
            ]);
        }
    }

    public function test_tenant_node_actions_write_audit_log_with_tenant_context(): void
    {
        $tenant = $this->quotaTenant;
        $user = $this->quotaActor;
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $tenant->update(['plan_id' => $this->quotaPlan->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNodeThroughApi($tenant, 'Audited Node');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => null,
            'action' => 'node "' . $node->name . '" created',
        ]);

        $this->putJson('/api/tenants/' . $tenant->id . '/nodes/' . $node->id, [
            'name' => 'Audited Node Updated',
        ])->assertOk();
        $node->refresh();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => null,
            'action' => 'node "Audited Node Updated" updated',
        ]);

        $nodeId = $node->id;
        $this->deleteJson('/api/tenants/' . $tenant->id . '/nodes/' . $node->id)
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => null,
            'action' => 'node "Audited Node Updated" deleted',
        ]);

        $deleteLog = AuditLog::query()
            ->where('action', 'node "Audited Node Updated" deleted')
            ->latest()
            ->firstOrFail();

        $this->assertSame($nodeId, $deleteLog->context['node_id']);
    }

    private function createTenantNode(Tenant $tenant, string $name): Node
    {
        return Node::query()->create([
            'tenant_id' => $tenant->id,
            'adapter' => FakeNodeAdapter::class,
            'name' => $name . ' ' . str()->ulid(),
            'hostname' => str($name)->slug() . '.local',
            'username' => 'root',
            'sudo' => true,
        ]);
    }

    private function createTenantNodeThroughApi(Tenant $tenant, string $name): Node
    {
        $nodeId = $this->postJson('/api/tenants/' . $tenant->id . '/nodes', [
            'adapter' => FakeNodeAdapter::class,
            'name' => $name,
            'hostname' => str($name)->slug() . '.local',
            'username' => 'root',
            'sudo' => true,
        ])
            ->assertCreated()
            ->json('data.id');

        return Node::query()->findOrFail($nodeId);
    }
}
