<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantResourceReservation;
use Froxlor\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_view_own_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id)
            ->assertOk();
    }

    public function test_tenant_admin_show_unknown_tenant(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . random_int(10000, 99999))
            ->assertNotFound();
    }

    public function test_user_cannot_view_unassigned_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id)
            ->assertForbidden();
    }

    public function test_tenant_tree_helpers_resolve_descendants_and_ancestors(): void
    {
        $rootTenant = Tenant::query()->root()->firstOrFail();
        $childTenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $grandchildTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();

        $this->assertContains($childTenant->id, $rootTenant->descendantIds());
        $this->assertContains($grandchildTenant->id, $rootTenant->descendantIds());
        $this->assertContains($rootTenant->id, $rootTenant->descendantIds(true));
        $this->assertNotContains($rootTenant->id, $rootTenant->descendantIds());
        $this->assertContains($grandchildTenant->id, $childTenant->descendantIds());
        $this->assertNotContains($rootTenant->id, $childTenant->descendantIds());
        $this->assertTrue($rootTenant->isAncestorOf($childTenant));
        $this->assertTrue($rootTenant->isAncestorOf($grandchildTenant));
        $this->assertFalse($childTenant->isAncestorOf($rootTenant));
        $this->assertTrue($grandchildTenant->isDescendantOf($rootTenant));
        $this->assertTrue($rootTenant->containsTenant($rootTenant, true));
        $this->assertFalse($rootTenant->containsTenant($rootTenant));
        $this->assertTrue($rootTenant->isParentToTenant($grandchildTenant));
    }

    public function test_tenant_tree_scopes_filter_root_children_and_tree(): void
    {
        $rootTenant = Tenant::query()->root()->firstOrFail();
        $childTenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $grandchildTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();

        $this->assertTrue(Tenant::query()->root()->pluck('id')->contains($rootTenant->id));
        $this->assertTrue(Tenant::query()->childrenOf($rootTenant)->pluck('id')->contains($childTenant->id));

        $treeIds = Tenant::query()->inTreeOf($rootTenant)->pluck('id');
        $descendantTreeIds = Tenant::query()->inTreeOf($rootTenant, false)->pluck('id');

        $this->assertTrue($treeIds->contains($rootTenant->id));
        $this->assertTrue($treeIds->contains($childTenant->id));
        $this->assertTrue($treeIds->contains($grandchildTenant->id));
        $this->assertFalse($descendantTreeIds->contains($rootTenant->id));
        $this->assertTrue($descendantTreeIds->contains($childTenant->id));
        $this->assertTrue($descendantTreeIds->contains($grandchildTenant->id));
    }

    public function test_child_tenant_creation_reserves_parent_budget(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resourceKey = 'reservation-test-' . str()->ulid();
        $resource = Resource::query()->create([
            'key' => $resourceKey,
            'name' => 'Reservation Test Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 2]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Reserved Child Tenant Plan ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 2]);

        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Reserved Child Tenant ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $plan->id,
            'resource_key' => $resourceKey,
            'resource_type' => 'tenant',
            'limit' => 2,
        ]);
    }

    public function test_child_tenant_creation_reserves_environment_resource_budget(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resourceKey = 'environment-reservation-test-' . str()->ulid();
        $resource = Resource::query()->create([
            'key' => $resourceKey,
            'name' => 'Environment Reservation Test Resource',
            'model_type' => Tenant::class,
            'type' => 'environment',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 2]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Reserved Child Environment Plan ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 2]);

        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Reserved Child Environment Tenant ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $plan->id,
            'resource_key' => $resourceKey,
            'resource_type' => 'environment',
            'limit' => 2,
        ]);
    }

    public function test_child_tenant_creation_rejects_plan_above_available_parent_budget(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resourceKey = 'reservation-test-' . str()->ulid();
        $resource = Resource::query()->create([
            'key' => $resourceKey,
            'name' => 'Reservation Test Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 2]);
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Exhausting Child Tenant Plan ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 2]);

        TenantResourceReservation::query()->create([
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => Tenant::query()->where('name', 'Kunde #2')->firstOrFail()->id,
            'plan_id' => $plan->id,
            'resource_key' => $resourceKey,
            'resource_type' => 'tenant',
            'limit' => 1,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'name' => 'Rejected Reserved Child Tenant ' . str()->ulid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_child_tenant_creation_requires_parent_owned_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $globalPlan = Plan::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $globalPlan->id,
                'name' => 'Rejected Global Plan Child Tenant ' . str()->ulid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_child_tenant_plan_update_syncs_reservations(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resource = Resource::query()->create([
            'key' => 'tenant-plan-switch-' . str()->ulid(),
            'name' => 'Tenant Plan Switch Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 3]);
        $initialPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Initial Tenant Switch Plan ' . str()->ulid(),
        ]);
        $initialPlan->resources()->attach($resource, ['limit' => 1]);
        $updatedPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Updated Tenant Switch Plan ' . str()->ulid(),
        ]);
        $updatedPlan->resources()->attach($resource, ['limit' => 2]);

        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $initialPlan->id,
                'name' => 'Tenant Plan Switch Child ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $childTenantId, [
                'plan_id' => $updatedPlan->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $updatedPlan->id,
            'resource_key' => $resource->key,
            'resource_type' => 'tenant',
            'limit' => 2,
        ]);
        $this->assertDatabaseMissing('tenant_resource_reservations', [
            'tenant_id' => $tenant->id,
            'reserved_for_tenant_id' => $childTenantId,
            'plan_id' => $initialPlan->id,
            'resource_key' => $resource->key,
        ]);
    }

    public function test_child_tenant_plan_update_cannot_drop_below_child_usage(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Limited')->firstOrFail()->id]);
        $resource = Resource::query()->create([
            'key' => 'tenant-plan-switch-usage-' . str()->ulid(),
            'name' => 'Tenant Plan Switch Usage Resource',
            'model_type' => Tenant::class,
            'type' => 'tenant',
        ]);
        $tenant->plan->resources()->attach($resource, ['limit' => 3]);
        $initialPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Initial Tenant Usage Switch Plan ' . str()->ulid(),
        ]);
        $initialPlan->resources()->attach($resource, ['limit' => 2]);
        $tooSmallPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Too Small Tenant Usage Switch Plan ' . str()->ulid(),
        ]);
        $tooSmallPlan->resources()->attach($resource, ['limit' => 1]);

        $childTenantId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants', [
                'parent_tenant_id' => $tenant->id,
                'plan_id' => $initialPlan->id,
                'name' => 'Tenant Usage Switch Child ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        DB::table('tenant_usage')->insert([
            'id' => (string)str()->ulid(),
            'tenant_id' => $childTenantId,
            'user_id' => $user->id,
            'resource_key' => $resource->key,
            'resource_id' => (string)str()->ulid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tenant_usage')->insert([
            'id' => (string)str()->ulid(),
            'tenant_id' => $childTenantId,
            'user_id' => $user->id,
            'resource_key' => $resource->key,
            'resource_id' => (string)str()->ulid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $childTenantId, [
                'plan_id' => $tooSmallPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }
}
