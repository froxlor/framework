<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class RolePolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.index');
    }

    public function view(User $user, Role $role): bool
    {
        if ($role->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.roles.index', $role->tenant);
        }

        return $user->hasPermission('roles.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.store');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.roles.update', $role->tenant);
        }

        return $user->hasPermission('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.roles.destroy', $role->tenant);
        }

        return $user->hasPermission('roles.destroy');
    }

    public function usersViewAny(User $user, Role $role): bool
    {
        return $role->tenant_id === null
            && $user->hasPermission('roles.users.index');
    }

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.roles.index', $tenant);
    }

    public function tenantView(User $user, Role $role, Tenant $tenant): bool
    {
        if ($role->tenant_id !== null && $role->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.roles.index', $tenant);
    }

    public function tenantCreate(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.roles.store', $tenant);
    }

    public function tenantUpdate(User $user, Role $role, Tenant $tenant): bool
    {
        if ($role->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.roles.update', $tenant);
    }

    public function tenantDelete(User $user, Role $role, Tenant $tenant): bool
    {
        if ($role->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.roles.destroy', $tenant);
    }
}
