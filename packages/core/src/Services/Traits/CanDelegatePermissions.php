<?php

namespace Froxlor\Core\Services\Traits;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\User;

trait CanDelegatePermissions
{
    /**
     * Determine whether the current assignment may delegate a permission to another role.
     *
     * Runtime permission checks only need to know whether a permission exists on one of the
     * assigned roles. Delegation is stricter: the matching permission, including wildcard
     * matches such as `*` or `roles.*`, must also be marked as inheritable on permission_role.
     */
    public function canDelegatePermission(string|array $permission): bool
    {
        $possiblePermissions = Permission::generatePermissionPath($permission);

        if ($this instanceof User) {
            return $this->roles()
                ->whereHas('permissions', function ($query) use ($possiblePermissions) {
                    $query->whereIn('key', $possiblePermissions)
                        ->where('permission_role.inheritable', true);
                })
                ->exists();
        }

        return $this->role()
            ->whereHas('permissions', function ($query) use ($possiblePermissions) {
                $query->whereIn('key', $possiblePermissions)
                    ->where('permission_role.inheritable', true);
            })
            ->exists();
    }
}
