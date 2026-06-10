<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantRoleAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_list_tenant_roles(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles')
            ->assertOk();
    }

    public function test_unassigned_user_cannot_list_tenant_roles(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles')
            ->assertForbidden();
    }

    public function test_global_permissions_do_not_bypass_tenant_access_scope(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->create([
            'first_name' => 'Global',
            'last_name' => 'Outsider',
            'email' => 'global-outsider-' . str()->ulid() . '@froxlor.test',
            'password' => bcrypt('secret'),
        ]);
        $user->roles()->attach(Role::query()->where('name', 'Super-Admin')->firstOrFail());

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles')
            ->assertForbidden();
    }

    public function test_tenant_admin_can_manage_tenant_role(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $roleId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/roles', [
                'name' => 'Tenant Policy Test Role ' . str()->ulid(),
                'description' => 'Created by TenantRoleAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles/' . $roleId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/roles/' . $roleId, [
                'description' => 'Updated by TenantRoleAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/roles/' . $roleId)
            ->assertNoContent();
    }

    public function test_unassigned_user_cannot_manage_tenant_role(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Forbidden Tenant Policy Test Role ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/roles', [
                'name' => 'Forbidden Tenant Role ' . str()->ulid(),
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id)
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_update_global_role_through_tenant_route(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id, [
                'description' => 'Forbidden global role update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id)
            ->assertForbidden();
    }
}
