<?php

namespace Tests\Feature;

use Froxlor\Core\Jobs\Node\ExploreNode;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeNodeAdapter;
use Tests\TestCase;

class TenantNodeAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/Fakes/FakeNodeAdapter.php';

        if (!in_array(FakeNodeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(FakeNodeAdapter::class);
        }
    }

    public function test_tenant_admin_can_manage_owned_tenant_node(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Tenant Policy Test Node ' . str()->ulid(),
                'hostname' => 'tenant-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
                'inheritable' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->json('data.id');

        Queue::assertPushed(ExploreNode::class);

        $this->assertTrue(
            Node::query()->findOrFail($nodeId)->isInheritableByTenant($tenant)
        );

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/nodes')
            ->assertOk()
            ->assertJsonFragment(['id' => $nodeId]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/nodes/' . $nodeId, [
                'name' => 'Updated Tenant Policy Test Node',
                'hostname' => 'updated-tenant-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/nodes/' . $nodeId)
            ->assertNoContent();
    }

    public function test_global_admin_can_create_tenant_node_from_global_nodes_endpoint(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Global Tenant Policy Test Node ' . str()->ulid(),
                'hostname' => 'global-tenant-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
                'tenant_id' => $tenant->id,
                'inheritable' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->json('data.id');

        Queue::assertPushed(ExploreNode::class);

        $this->assertTrue(
            Node::query()->findOrFail($nodeId)->isInheritableByTenant($tenant)
        );
    }

    public function test_tenant_can_inherit_only_inheritable_nodes_when_creating_sub_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Inherited Node Tenant Plan ' . str()->ulid(),
        ]);

        $inheritableNode = Node::query()->create([
            'tenant_id' => $tenant->id,
            'adapter' => FakeNodeAdapter::class,
            'name' => 'Inheritable Policy Test Node ' . str()->ulid(),
            'hostname' => 'inheritable-node-policy-test.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $tenant->nodes()->attach($inheritableNode, ['inheritable' => true]);

        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Inherited Node Tenant ' . str()->ulid(),
                'nodes' => [
                    ['id' => $inheritableNode->id, 'inheritable' => false],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertTrue(
            Node::query()->findOrFail($inheritableNode->id)
                ->isAvailableForTenant(Tenant::query()->findOrFail($childTenantId))
        );

        $nonInheritableNode = Node::query()->create([
            'tenant_id' => $tenant->id,
            'adapter' => FakeNodeAdapter::class,
            'name' => 'Non Inheritable Policy Test Node ' . str()->ulid(),
            'hostname' => 'non-inheritable-node-policy-test.local',
            'username' => 'root',
            'sudo' => true,
        ]);
        $tenant->nodes()->attach($nonInheritableNode, ['inheritable' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Rejected Inherited Node Tenant ' . str()->ulid(),
                'nodes' => [
                    ['id' => $nonInheritableNode->id, 'inheritable' => false],
                ],
            ])
            ->assertUnprocessable();
    }
}
