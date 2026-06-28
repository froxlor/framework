<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class RolePermissionAuthorizationTest extends TestCase
{
    public function test_super_admin_can_manage_global_role_permissions(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();

        $role->permissions()->detach($permission);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/permissions')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles/' . $role->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => false,
            ])
            ->assertOk();

        $this->assertFalse((bool)$role->permissions()
            ->where('permissions.id', $permission->id)
            ->firstOrFail()
            ->pivot
            ->inheritable);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission "' . $permission->key . '" assigned to role "' . $role->name . '"',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission "' . $permission->key . '" removed from role "' . $role->name . '"',
        ]);
    }

    public function test_role_permission_index_lists_assigned_and_unassigned_permissions(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $assignedPermission = Permission::query()->where('key', 'users.index')->firstOrFail();
        $unassignedPermission = Permission::query()->where('key', 'users.store')->firstOrFail();

        $role->permissions()->syncWithoutDetaching([
            $assignedPermission->id => ['inheritable' => true],
        ]);
        $role->permissions()->detach($unassignedPermission);

        $permissions = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/permissions')
            ->assertOk()
            ->json('data'));

        $assigned = $permissions->firstWhere('id', $assignedPermission->id);
        $unassigned = $permissions->firstWhere('id', $unassignedPermission->id);

        $this->assertSame($assignedPermission->key, $assigned['key']);
        $this->assertTrue($assigned['assigned']);
        $this->assertTrue($assigned['inheritable']);
        $this->assertSame($unassignedPermission->key, $unassigned['key']);
        $this->assertFalse($unassigned['assigned']);
        $this->assertFalse($unassigned['inheritable']);
    }

    public function test_global_role_permission_store_persists_inheritable_true(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.store')->firstOrFail();

        $role->permissions()->detach($permission);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles/' . $role->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => true,
            ])
            ->assertOk();

        $this->assertTrue((bool)$role->permissions()
            ->where('permissions.id', $permission->id)
            ->firstOrFail()
            ->pivot
            ->inheritable);
    }

    public function test_tenant_admin_cannot_manage_global_role_permissions(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/permissions')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles/' . $role->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertForbidden();
    }

    public function test_user_cannot_assign_permission_without_inheritable_delegation(): void
    {
        $user = User::query()->create([
            'first_name' => 'Limited',
            'last_name' => 'SysAdmin',
            'email' => 'limited-sysadmin-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $userRole = Role::query()->create([
            'name' => 'Limited SysAdmin ' . str()->ulid(),
        ]);
        $targetRole = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();
        $wildcardPermission = Permission::query()->where('key', '*')->firstOrFail();

        $targetRole->permissions()->detach($permission);
        $userRole->permissions()->attach($wildcardPermission, ['inheritable' => false]);
        $user->roles()->attach($userRole);

        $this->assertTrue($user->hasPermission('roles.permissions.store'));
        $this->assertFalse($user->canDelegatePermission($permission->key));

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles/' . $targetRole->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => false,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_remove_permission_without_inheritable_delegation(): void
    {
        $user = User::query()->create([
            'first_name' => 'Limited',
            'last_name' => 'Permission Remover',
            'email' => 'limited-permission-remover-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $userRole = Role::query()->create([
            'name' => 'Limited Permission Remover ' . str()->ulid(),
        ]);
        $targetRole = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();
        $wildcardPermission = Permission::query()->where('key', '*')->firstOrFail();

        $targetRole->permissions()->syncWithoutDetaching([
            $permission->id => ['inheritable' => false],
        ]);
        $userRole->permissions()->attach($wildcardPermission, ['inheritable' => false]);
        $user->roles()->attach($userRole);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $targetRole->id . '/permissions/' . $permission->id)
            ->assertForbidden();
    }

    public function test_user_cannot_remove_permission_from_own_global_role(): void
    {
        $user = User::query()->create([
            'first_name' => 'Self',
            'last_name' => 'Locked',
            'email' => 'self-locked-global-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'name' => 'Self Locked Global Role ' . str()->ulid(),
        ]);
        $wildcardPermission = Permission::query()->where('key', '*')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();

        $role->permissions()->attach($wildcardPermission, ['inheritable' => true]);
        $role->permissions()->attach($permission, ['inheritable' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);

        $this->assertTrue(
            $role->permissions()->where('permissions.id', $permission->id)->exists()
        );
    }

    public function test_detaching_unassigned_global_role_permission_returns_validation_error(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Reseller')->firstOrFail();
        $permission = Permission::query()->where('key', 'users.update')->firstOrFail();

        $role->permissions()->detach($permission);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permission_id']);
    }

    public function test_global_role_permission_route_does_not_manage_tenant_roles(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Role Permission Policy Test ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'users.index')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/permissions')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles/' . $role->id . '/permissions', [
                'permission_id' => $permission->id,
                'inheritable' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id . '/permissions/' . $permission->id)
            ->assertForbidden();
    }
}
