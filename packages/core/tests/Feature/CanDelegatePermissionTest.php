<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\EnvironmentUser;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantUser;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class CanDelegatePermissionTest extends TestCase
{
    public function test_global_user_can_delegate_permission_through_inheritable_wildcard(): void
    {
        $user = User::query()->create([
            'first_name' => 'Delegating',
            'last_name' => 'Admin',
            'email' => 'delegating-admin-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'name' => 'Delegating Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'roles.*')->firstOrFail();

        $role->permissions()->attach($permission, ['inheritable' => true]);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('roles.permissions.store'));
        $this->assertTrue($user->canDelegatePermission('roles.permissions.store'));
    }

    public function test_global_user_cannot_delegate_non_inheritable_permission(): void
    {
        $user = User::query()->create([
            'first_name' => 'Executing',
            'last_name' => 'Admin',
            'email' => 'executing-admin-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'name' => 'Executing Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', '*')->firstOrFail();

        $role->permissions()->attach($permission, ['inheritable' => false]);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('users.index'));
        $this->assertFalse($user->canDelegatePermission('users.index'));
    }

    public function test_tenant_user_can_delegate_only_inheritable_scoped_permissions(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->create([
            'first_name' => 'Tenant',
            'last_name' => 'Delegator',
            'email' => 'tenant-delegator-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Delegating Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'tenants.roles.*')->firstOrFail();

        $role->permissions()->attach($permission, ['inheritable' => true]);
        $user->tenants()->attach($tenant, ['role_id' => $role->id]);

        $tenantUser = TenantUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertTrue($tenantUser->hasPermission('tenants.roles.store'));
        $this->assertTrue($tenantUser->canDelegatePermission('tenants.roles.store'));
        $this->assertFalse($tenantUser->canDelegatePermission('tenants.users.store'));
    }

    public function test_environment_user_can_delegate_only_inheritable_scoped_permissions(): void
    {
        $environment = Environment::query()->where('name', 'Kunden Environment')->firstOrFail();
        $user = User::query()->create([
            'first_name' => 'Environment',
            'last_name' => 'Delegator',
            'email' => 'environment-delegator-' . str()->ulid() . '@froxlor.test',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'name' => 'Environment Delegating Role ' . str()->ulid(),
        ]);
        $permission = Permission::query()->where('key', 'tenants.environments.*')->firstOrFail();

        $role->permissions()->attach($permission, ['inheritable' => true]);
        $user->environments()->attach($environment, ['role_id' => $role->id]);

        $environmentUser = EnvironmentUser::query()
            ->where('environment_id', $environment->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertTrue($environmentUser->hasPermission('tenants.environments.update'));
        $this->assertTrue($environmentUser->canDelegatePermission('tenants.environments.update'));
        $this->assertFalse($environmentUser->canDelegatePermission('tenants.roles.store'));
    }
}
