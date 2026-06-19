<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\PermissionRegistry;
use Illuminate\Database\Seeder;

class RolesAndPermissionsTableSeeder extends Seeder
{
    /**
     * Seed model-defined permissions and baseline global roles.
     *
     * Permissions are discovered from core models using `HasPermissions`; they are not
     * intended to be created through API CRUD. The Super-Admin role receives `*` with an
     * inheritable pivot so the bootstrap user can delegate every permission.
     *
     * @return void
     */
    public function run(): void
    {
        PermissionRegistry::registerPackageModelsFrom(dirname(__DIR__, 3));
        PermissionRegistry::sync();

        // id=1 super-admin (everything allowed)
        self::createRoleWithPermissions('Super-Admin', [
            ['key' => '*', 'inheritable' => true]
        ]);

        // id=2 admin (everything allowed in tenant scope)
        self::createRoleWithPermissions('Admin', [
            ['key' => 'tenants.*', 'inheritable' => true],
        ]);

        // id=3 reseller, only allow adding plans and roles
        self::createRoleWithPermissions('Reseller', [
            ['key' => 'plans.store'],
            ['key' => 'roles.store'],
        ]);

        self::createRoleWithPermissions('Environment-Owner', [
            ['key' => 'tenants.environments.*', 'inheritable' => true]
        ]);
    }

    /**
     * Create a global role and attach permission keys with optional inheritance metadata.
     *
     * @param string $string Human readable role name.
     * @param array<int, array{key: string, inheritable?: bool}> $permissionKeys
     */
    public static function createRoleWithPermissions(string $string, array $permissionKeys): Role
    {
        /** @var Role $role */
        $role = Role::query()->create([
            'name' => $string,
        ]);

        foreach ($permissionKeys as $permissionKey) {
            $permission = Permission::query()->where('key', $permissionKey['key'])->firstOr(function () use ($permissionKey) {
                throw new \Exception('Using permission key "' . $permissionKey['key'] . '" without it being defined in the corresponding model.');
            });
            $role->permissions()->attach($permission, ['inheritable' => $permissionKey['inheritable'] ?? false]);
        }

        return $role;
    }
}
