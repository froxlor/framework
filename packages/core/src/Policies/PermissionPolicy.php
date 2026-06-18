<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\User;

class PermissionPolicy
{
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
}
