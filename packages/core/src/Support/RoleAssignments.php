<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleAssignments
{
    /**
     * Ensure that a role can be assigned in the requested scope by the acting user.
     *
     * Role assignment is permission delegation. The role must be available in the target
     * scope and every permission on the role must be delegable by the actor either through
     * global inheritable permissions or through the matching tenant/environment pivot role.
     *
     * @throws ValidationException
     */
    public static function ensureAssignable(
        User $actor,
        ?string $roleId,
        string $field = 'role_id',
        ?Tenant $tenant = null,
        ?Environment $environment = null,
    ): void {
        if (empty($roleId)) {
            return;
        }

        $role = Role::query()->with('permissions')->findOrFail($roleId);

        self::ensureRoleAvailableInScope($role, $field, $tenant);

        foreach ($role->permissions as $permission) {
            if (!self::canDelegate($actor, $permission->key, $tenant, $environment)) {
                throw ValidationException::withMessages([
                    $field => 'The selected role contains permissions you cannot delegate.',
                ]);
            }
        }
    }

    /**
     * Determine whether the actor can delegate a concrete permission in the given scope.
     */
    public static function canDelegate(
        User $actor,
        string $permission,
        ?Tenant $tenant = null,
        ?Environment $environment = null,
    ): bool {
        if ($actor->canDelegatePermission($permission)) {
            return true;
        }

        if ($environment !== null) {
            if ($tenant !== null && $environment->tenant_id !== $tenant->id) {
                return false;
            }

            $environmentUser = $environment->users()
                ->where('users.id', $actor->id)
                ->first();

            return (bool)$environmentUser?->pivot?->canDelegatePermission($permission);
        }

        if ($tenant !== null) {
            $tenantUser = $tenant->users()
                ->where('users.id', $actor->id)
                ->first();

            return (bool)$tenantUser?->pivot?->canDelegatePermission($permission);
        }

        return false;
    }

    /**
     * Ensure a role is not assigned to any user before deletion.
     *
     * Roles are security-bearing objects. Deleting an assigned role would either fail at
     * database level or leave users without a clear authorization state, so controllers
     * should reject the operation with a validation response first.
     *
     * @throws ValidationException
     */
    public static function ensureNotAssigned(Role $role): void
    {
        $assignments = [
            'global users' => DB::table('role_user')->where('role_id', $role->id)->count(),
            'tenant users' => DB::table('tenant_user')->where('role_id', $role->id)->count(),
            'environment users' => DB::table('environment_user')->where('role_id', $role->id)->count(),
        ];

        $usedBy = collect($assignments)
            ->filter()
            ->keys()
            ->implode(', ');

        if ($usedBy !== '') {
            throw ValidationException::withMessages([
                'role' => 'The role is still assigned to ' . $usedBy . '.',
            ]);
        }
    }

    /**
     * Ensure the given role is not currently assigned to the user in the active scope.
     *
     * Permission changes on a user's own role can immediately remove the permission
     * required to recover from the change, so role-permission mutations must reject
     * those self-referential updates before touching the pivot table.
     *
     * @throws ValidationException
     */
    public static function ensureNotAssignedToUser(
        Role $role,
        User $user,
        ?Tenant $tenant = null,
        ?Environment $environment = null,
    ): void {
        $assigned = match (true) {
            $environment !== null => DB::table('environment_user')
                ->where('environment_id', $environment->id)
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists(),
            $tenant !== null => DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists(),
            default => DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists(),
        };

        if ($assigned) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change permissions on a role assigned to yourself.',
            ]);
        }
    }

    /**
     * Ensure the selected role is global or owned by the target tenant.
     *
     * Global roles are available in tenant/environment scopes. Tenant-owned roles are only
     * assignable inside their owning tenant tree context.
     *
     * @throws ValidationException
     */
    private static function ensureRoleAvailableInScope(Role $role, string $field, ?Tenant $tenant = null): void
    {
        if ($tenant === null && $role->tenant_id !== null) {
            throw ValidationException::withMessages([
                $field => 'The selected role is not available globally.',
            ]);
        }

        if ($tenant !== null && $role->tenant_id !== null && $role->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages([
                $field => 'The selected role is not available for this tenant.',
            ]);
        }
    }
}
