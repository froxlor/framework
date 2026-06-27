<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Support\PermissionRegistry;
use Froxlor\Core\Services\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    public function test_permission_registry_registers_model_permissions(): void
    {
        $model = new class extends Model {
            use HasPermissions;

            /**
             * Return deterministic permissions for PermissionRegistry contract tests.
             *
             * @return array<int, array{key: string, name: string}>
             */
            public static function getAllPermissions(): array
            {
                return [
                    ['key' => 'tests.fake-permission-model.index', 'name' => 'View fake permission model'],
                ];
            }
        };

        PermissionRegistry::registerModel($model::class, 'tests/package-model');

        $permission = collect(PermissionRegistry::all())
            ->firstWhere('key', 'tests.fake-permission-model.index');

        $this->assertSame('View fake permission model', $permission['name']);
        $this->assertSame('tests/package-model', $permission['source']);
    }

    public function test_permission_registry_rejects_conflicting_permission_keys(): void
    {
        PermissionRegistry::register([
            ['key' => 'tests.registry.conflict', 'name' => 'First registration'],
        ], 'tests/package-a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Permission key "tests.registry.conflict" is already registered by "tests/package-a"');

        PermissionRegistry::register([
            ['key' => 'tests.registry.conflict', 'name' => 'Second registration'],
        ], 'tests/package-b');
    }

}
