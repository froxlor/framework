<?php

namespace Froxlor\Core\Database\Seeders;

use FilesystemIterator;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Services\Traits\HasPermissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // set up all permission from the models with trait "hasPermissions"
        $fileSystemIterator = new FilesystemIterator(dirname(__DIR__, 2) . '/src/Models');

        foreach ($fileSystemIterator as $fileInfo) {
            if ($fileInfo->getFilename() != '.' && $fileInfo->getFilename() != '..' && !$fileInfo->isDir()) {
                self::createPermissions(substr($fileInfo->getFilename(), 0, -4));
            }
        }

        // id=1 super-admin (everything allowed)
        self::createRoleWithPermissions('Super-Admin', [
            ['key' => '*', 'inheritable' => true]
        ]);

        // id=2 admin (everything allowed in tenant scope)
        self::createRoleWithPermissions('Admin', [
            ['key' => 'tenants.*'],
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

    private static function createPermissions(string $modelName): void
    {
        $modelFQCN = "\\Froxlor\\Core\\Models\\" . $modelName;
        $model = new $modelFQCN();
        if (in_array(HasPermissions::class, class_uses_recursive($model))) {
            foreach ($model::getAllPermissions() as $permission) {
                Permission::query()->where('key', $permission['key'])->firstOrCreate([
                    'key' => $permission['key'],
                    'name' => $permission['name'],
                ]);
            }
        }
    }

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
