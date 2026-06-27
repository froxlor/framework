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

class NodeResourceUsageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/Fakes/FakeNodeAdapter.php';

        if (!in_array(FakeNodeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(FakeNodeAdapter::class);
        }
    }

    public function test_tenant_owned_node_creates_and_removes_resource_usage(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

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
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
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

    public function test_parent_tenant_user_creating_node_for_subtenant_counts_usage_on_both_tenants(): void
    {
        $parentTenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $subTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $parentTenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $subTenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $parentTenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);
        $subTenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNode($subTenant, 'Forced Subtenant Node');

        $this->assertDatabaseHas('tenant_usage', [
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
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail();
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
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Node::getResourceKey())->delete();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

        $this->actingAs($user, 'sanctum');

        $node = $this->createTenantNode($tenant, 'Audited Node');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => null,
            'action' => 'node "' . $node->name . '" created',
        ]);

        $node->update(['name' => 'Audited Node Updated']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => null,
            'action' => 'node "Audited Node Updated" updated',
        ]);

        $nodeId = $node->id;
        $node->delete();

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
}
