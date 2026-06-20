<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class PermissionPolicy
{
    use ResolvesScopedPermissions;

    public function viewAvailable(User $user): bool
    {
        return $user->hasPermission('roles.permissions.available');
    }

    public function roleViewAny(User $user, Role $role): bool
    {
        return $role->tenant_id === null
            && $user->hasPermission('roles.permissions.index');
    }

    public function roleCreate(User $user, Role $role): bool
    {
        return $role->tenant_id === null
            && $user->hasPermission('roles.permissions.store');
    }

    public function roleDelete(User $user, Permission $permission, Role $role): bool
    {
        return $role->tenant_id === null
            && $user->hasPermission('roles.permissions.destroy');
    }

    public function tenantRoleViewAny(User $user, Tenant $tenant, Role $role): bool
    {
        return $role->tenant_id === $tenant->id
            && $this->hasScopedPermission($user, 'tenants.roles.permissions.index', $tenant);
    }

    public function tenantRoleCreate(User $user, Tenant $tenant, Role $role): bool
    {
        return $role->tenant_id === $tenant->id
            && $this->hasScopedPermission($user, 'tenants.roles.permissions.store', $tenant);
    }

    public function tenantRoleDelete(User $user, Permission $permission, Tenant $tenant, Role $role): bool
    {
        return $role->tenant_id === $tenant->id
            && $this->hasScopedPermission($user, 'tenants.roles.permissions.destroy', $tenant);
    }
}
