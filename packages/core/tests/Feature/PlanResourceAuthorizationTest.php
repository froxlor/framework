<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class PlanResourceAuthorizationTest extends TestCase
{
    public function test_super_admin_can_manage_global_plan_resources(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Tenant Starter')->firstOrFail();
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $basePath = '/api/plans/' . $plan->id . '/resources';

        $plan->resources()->detach($resource);

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'resource_id' => $resource->id,
                'limit' => 4,
            ])
            ->assertOk();

        $this->assertSame(4, (int)$plan->resources()
            ->where('resources.id', $resource->id)
            ->firstOrFail()
            ->pivot
            ->limit);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'resource "' . $resource->key . '" assigned to plan "' . $plan->name . '"',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $resource->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'resource "' . $resource->key . '" removed from plan "' . $plan->name . '"',
        ]);
    }

    public function test_plan_resource_index_lists_assigned_and_unassigned_resources(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Tenant Starter')->firstOrFail();
        $assignedResource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $unassignedResource = Resource::query()->where('type', 'tenant')->where('key', 'roles')->firstOrFail();

        $plan->resources()->syncWithoutDetaching([
            $assignedResource->id => ['limit' => 3],
        ]);
        $plan->resources()->detach($unassignedResource);

        $resources = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $plan->id . '/resources')
            ->assertOk()
            ->json('data'));

        $assigned = $resources->firstWhere('id', $assignedResource->id);
        $unassigned = $resources->firstWhere('id', $unassignedResource->id);

        $this->assertTrue($assigned['assigned']);
        $this->assertSame(3, $assigned['limit']);
        $this->assertFalse($unassigned['assigned']);
        $this->assertSame(0, $unassigned['limit']);
    }

    public function test_plan_resource_route_rejects_tenant_plans(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Global Route Tenant Plan ' . str()->ulid(),
        ]);
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $plan->id . '/resources')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 1,
            ])
            ->assertForbidden();
    }

    public function test_global_plan_can_assign_environment_resource(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Tenant Starter')->firstOrFail();
        $resource = Resource::query()->where('type', 'environment')->where('key', 'users')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 1,
            ])
            ->assertOk();
    }

    public function test_detaching_unassigned_global_plan_resource_returns_validation_error(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Tenant Starter')->firstOrFail();
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'roles')->firstOrFail();

        $plan->resources()->detach($resource);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/plans/' . $plan->id . '/resources/' . $resource->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource_id']);
    }
}
