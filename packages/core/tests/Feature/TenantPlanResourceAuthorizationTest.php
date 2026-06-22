<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantUsage;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantPlanResourceAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_tenant_plan_resources(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $originalPlanId = $tenant->plan_id;
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Resource Plan ' . str()->ulid(),
        ]);
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources';

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'resource_id' => $resource->id,
                'limit' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'resource "' . $resource->key . '" assigned to plan "' . $plan->name . '"',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $resource->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'resource "' . $resource->key . '" removed from plan "' . $plan->name . '"',
        ]);
    }

    public function test_tenant_plan_resource_index_lists_assigned_and_unassigned_resources(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Resource Listing Plan ' . str()->ulid(),
        ]);
        $assignedResource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $unassignedResource = Resource::query()->where('type', 'tenant')->where('key', 'roles')->firstOrFail();

        $plan->resources()->attach($assignedResource, ['limit' => 1]);

        $resources = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources')
            ->assertOk()
            ->json('data'));

        $assigned = $resources->firstWhere('id', $assignedResource->id);
        $unassigned = $resources->firstWhere('id', $unassignedResource->id);

        $this->assertTrue($assigned['assigned']);
        $this->assertSame(1, $assigned['limit']);
        $this->assertFalse($unassigned['assigned']);
        $this->assertSame(0, $unassigned['limit']);
    }

    public function test_tenant_plan_resource_route_rejects_foreign_and_global_plans(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $foreignPlan = Plan::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Tenant Resource Plan ' . str()->ulid(),
        ]);
        $globalPlan = Plan::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans/' . $foreignPlan->id . '/resources')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans/' . $globalPlan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 1,
            ])
            ->assertForbidden();
    }

    public function test_tenant_plan_resource_limit_must_not_exceed_tenant_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update([
            'plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id,
        ]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Resource Limit Plan ' . str()->ulid(),
        ]);
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_detaching_unassigned_tenant_plan_resource_returns_validation_error(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Unassigned Tenant Resource Plan ' . str()->ulid(),
        ]);
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'roles')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources/' . $resource->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource_id']);
    }

    public function test_assigned_tenant_plan_resources_update_child_reservations(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resource = Resource::query()->create([
            'key' => 'reservation-update-' . str()->ulid(),
            'name' => 'Reservation Update Test Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 2]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Assigned Tenant Resource Plan ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 1]);
        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Tenant Plan Resource Child ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 2,
            ])
            ->assertOk();

        $this->assertDatabaseHas('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $plan->id,
            'resource_key' => $resource->key,
            'resource_type' => $resource->type,
            'limit' => 2,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $plan->id,
            'resource_key' => $resource->key,
            'resource_type' => $resource->type,
            'limit' => 1,
        ]);
    }

    public function test_assigned_tenant_plan_resource_limit_cannot_drop_below_child_usage(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resource = Resource::query()->create([
            'key' => 'reservation-usage-' . str()->ulid(),
            'name' => 'Reservation Usage Test Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 2]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Assigned Tenant Resource Usage Plan ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 1]);
        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Tenant Plan Resource Usage Child ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        TenantUsage::query()->create([
            'tenant_id' => $childTenantId,
            'user_id' => $user->id,
            'resource_key' => $resource->key,
            'resource_id' => User::query()->where('email', 'dev3@froxlor.org')->firstOrFail()->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources', [
                'resource_id' => $resource->id,
                'limit' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id . '/resources/' . $resource->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);
    }
}
