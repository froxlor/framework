<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantRolePermissionAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_tenant_role_permissions(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Permission Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'tenants.users.index')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/roles/' . $role->id . '/permissions';

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'permission_id' => $permission->id,
                'inheritable' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'permission "' . $permission->key . '" assigned to role "' . $role->name . '"',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $permission->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'permission "' . $permission->key . '" removed from role "' . $role->name . '"',
        ]);
    }

    public function test_tenant_role_permission_index_lists_assigned_and_unassigned_permissions(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Permission Listing Role ' . str()->ulid(),
        ]);
        $assignedPermission = Permission::query()->where('key', 'tenants.users.index')->firstOrFail();
        $unassignedPermission = Permission::query()->where('key', 'tenants.users.store')->firstOrFail();

        $role->permissions()->syncWithoutDetaching([
            $assignedPermission->id => ['inheritable' => true],
        ]);

        $permissions = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id . '/permissions')
            ->assertOk()
            ->json('data'));

        $assigned = $permissions->firstWhere('id', $assignedPermission->id);
        $unassigned = $permissions->firstWhere('id', $unassignedPermission->id);

        $this->assertTrue($assigned['assigned']);
        $this->assertTrue($assigned['inheritable']);
        $this->assertFalse($unassigned['assigned']);
        $this->assertFalse($unassigned['inheritable']);
    }

    public function test_tenant_admin_cannot_assign_non_delegable_permission_to_tenant_role(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Forbidden Tenant Permission Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', '*')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => false,
            ])
            ->assertForbidden();
    }

    public function test_tenant_role_permission_route_rejects_foreign_and_global_roles(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $permission = Permission::query()->where('key', 'tenants.users.index')->firstOrFail();
        $foreignRole = Role::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Tenant Permission Role ' . str()->ulid(),
        ]);
        $globalRole = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/roles/' . $foreignRole->id . '/permissions')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/roles/' . $globalRole->id . '/permissions', [
                'permission_id' => $permission->id,
            ])
            ->assertForbidden();
    }

    public function test_detaching_unassigned_tenant_role_permission_returns_validation_error(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Unassigned Tenant Permission Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'tenants.users.index')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permission_id']);
    }
}
