<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantUserAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();

        $userId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'User',
                'email' => 'tenant-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users/' . $userId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $userId, [
                'first_name' => 'Updated',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/users/' . $userId)
            ->assertOk();
    }

    public function test_tenant_admin_can_assign_tenant_owned_role_to_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User Assignment Role ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'Role User',
                'email' => 'tenant-role-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertCreated();
    }

    public function test_tenant_user_plan_must_stay_within_tenant_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();
        $resource = Resource::query()->where('key', 'users')->where('type', 'tenant')->firstOrFail();

        $parentPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Parent User Plan ' . str()->ulid(),
        ]);
        $parentPlan->resources()->attach($resource, ['limit' => 2]);
        $tenant->update(['plan_id' => $parentPlan->id]);

        $validPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Child User Plan ' . str()->ulid(),
        ]);
        $validPlan->resources()->attach($resource, ['limit' => 1]);

        $tooLargePlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Too Large User Plan ' . str()->ulid(),
        ]);
        $tooLargePlan->resources()->attach($resource, ['limit' => 3]);

        $unlimitedPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Unlimited User Plan ' . str()->ulid(),
        ]);
        $unlimitedPlan->resources()->attach($resource, ['limit' => -1]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'Plan User',
                'email' => 'tenant-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
                'plan_id' => $validPlan->id,
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Large Plan User',
                'email' => 'tenant-large-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
                'plan_id' => $tooLargePlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Unlimited Plan User',
                'email' => 'tenant-unlimited-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
                'plan_id' => $unlimitedPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Wrong Type Plan User',
                'email' => 'tenant-wrong-type-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
                'plan_id' => Plan::query()->whereNull('tenant_id')->where('name', 'Platform Unlimited')->firstOrFail()->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_tenant_user_plan_update_cannot_drop_below_current_usage(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();
        $resource = Resource::query()->where('key', 'users')->where('type', 'tenant')->firstOrFail();
        $parentPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User Usage Parent Plan ' . str()->ulid(),
        ]);
        $parentPlan->resources()->attach($resource, ['limit' => 3]);
        $tenant->update(['plan_id' => $parentPlan->id]);
        $initialPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User Usage Initial Plan ' . str()->ulid(),
        ]);
        $initialPlan->resources()->attach($resource, ['limit' => 2]);
        $tooSmallPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User Usage Too Small Plan ' . str()->ulid(),
        ]);
        $tooSmallPlan->resources()->attach($resource, ['limit' => 1]);

        $targetUserId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'Usage Plan User',
                'email' => 'tenant-usage-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
                'plan_id' => $initialPlan->id,
            ])
            ->assertCreated()
            ->json('data.id');

        DB::table('tenant_usage')->insert([
            'id' => (string)str()->ulid(),
            'tenant_id' => $tenant->id,
            'user_id' => $targetUserId,
            'resource_key' => $resource->key,
            'resource_id' => (string)str()->ulid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tenant_usage')->insert([
            'id' => (string)str()->ulid(),
            'tenant_id' => $tenant->id,
            'user_id' => $targetUserId,
            'resource_key' => $resource->key,
            'resource_id' => (string)str()->ulid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUserId, [
                'plan_id' => $tooSmallPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_tenant_admin_cannot_assign_role_from_another_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Tenant User Assignment Role ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Foreign Role User',
                'email' => 'forbidden-foreign-role-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id, [
                'role_id' => $role->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_tenant_admin_cannot_assign_role_with_non_delegable_permissions(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'Super-Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Super Admin User',
                'email' => 'forbidden-super-admin-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $superAdminRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);

        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id, [
                'role_id' => $superAdminRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_unassigned_user_cannot_manage_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Tenant User',
                'email' => 'forbidden-tenant-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id, [
                'first_name' => 'Forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id)
            ->assertForbidden();
    }
}
