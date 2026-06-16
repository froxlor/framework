<?php

namespace Tests\Feature;

use Froxlor\Core\Jobs\Node\ExploreNode;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Node\Adapter\Local;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NodeAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_nodes(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_nodes(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes')
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_node(): void
    {
        Queue::fake();

        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => Local::class,
                'name' => 'Policy Test Node ' . str()->ulid(),
                'hostname' => 'node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        Queue::assertPushed(ExploreNode::class);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes/' . $nodeId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $nodeId, [
                'name' => 'Updated Policy Test Node',
                'hostname' => 'updated-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/nodes/' . $nodeId)
            ->assertNoContent();
    }

    public function test_node_adapter_must_be_registered_adapter_fqcn(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => 'not-a-class',
                'name' => 'Invalid Adapter Policy Test Node ' . str()->ulid(),
                'hostname' => 'invalid-adapter-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['adapter']);
    }

    public function test_tenant_admin_cannot_manage_node(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $node = Node::query()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes/' . $node->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => Local::class,
                'name' => 'Forbidden Policy Test Node ' . str()->ulid(),
                'hostname' => 'forbidden-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $node->id, [
                'name' => 'Forbidden Node Update',
                'hostname' => 'forbidden-node-update.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/nodes/' . $node->id)
            ->assertForbidden();
    }
}
