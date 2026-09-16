<?php

namespace Froxlor\Core\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Serialize security mutations and preserve the last recoverable global administrator. */
final class AdministrationGuard
{
    /**
     * Call before any reads/writes in a mutation that can remove global administration.
     * Packages modifying global role assignments must use the same transaction boundary.
     * The immutable bootstrap permission row is the shared database mutex, not a cache lock.
     */
    public static function run(Closure $mutation): mixed
    {
        return DB::transaction(function () use ($mutation): mixed {
            $permission = DB::table('permissions')->where('key', '*')->lockForUpdate()->first();
            if ($permission === null) {
                throw ValidationException::withMessages(['administration' => 'The global administration permission is missing. Restore the permission registry first.']);
            }

            $before = self::administrators($permission->id);
            $roots = self::administeredRoots($before);
            $result = $mutation();

            $after = self::administrators($permission->id);
            if (($before !== [] && $after === [])
                || array_diff($roots, self::administeredRoots($after)) !== []) {
                throw ValidationException::withMessages([
                    'administration' => 'This change would remove the last global administrator or their root tenant access.',
                ]);
            }

            return $result;
        });
    }

    /** Current/locking reads also work under MariaDB REPEATABLE READ. Role names are irrelevant. */
    private static function administrators(string $permissionId): array
    {
        return DB::table('users')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('permission_role', 'permission_role.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->whereNull('roles.tenant_id')
            ->where('permission_role.permission_id', $permissionId)
            ->where('permission_role.inheritable', true)
            ->orderBy('users.id')->lockForUpdate()
            ->pluck('users.id')->unique()->values()->all();
    }

    /** Preserve each existing root's administrator membership, not only global permissions. */
    private static function administeredRoots(array $administrators): array
    {
        if ($administrators === []) {
            return [];
        }

        return DB::table('tenant_user')
            ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->whereNull('tenants.parent_tenant_id')
            ->whereIn('tenant_user.user_id', $administrators)
            ->orderBy('tenants.id')->lockForUpdate()
            ->pluck('tenants.id')->unique()->values()->all();
    }
}
