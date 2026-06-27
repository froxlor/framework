<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Core\Services\Traits\IsTenantResource;
use Froxlor\Core\Support\ResourceRegistry;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Models\MailAddress;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Tests\TestCase;

class ResourceRegistryTest extends TestCase
{
    public function test_resource_registry_registers_model_resources(): void
    {
        $model = new class extends Model {
            use IsResource;
            use IsTenantResource;
            use IsEnvironmentResource;

            /**
             * Return a deterministic key for ResourceRegistry contract tests.
             */
            public static function getResourceKey(): string
            {
                return 'tests.fake-resource-models';
            }
        };

        ResourceRegistry::registerModel($model::class, 'tests/package-model');

        $tenantResource = collect(ResourceRegistry::all())
            ->first(fn(array $resource) => $resource['key'] === 'tests.fake-resource-models' && $resource['type'] === 'tenant');
        $environmentResource = collect(ResourceRegistry::all())
            ->first(fn(array $resource) => $resource['key'] === 'tests.fake-resource-models' && $resource['type'] === 'environment');

        $this->assertSame($model::class, $tenantResource['model_type']);
        $this->assertSame('Tenant Tests Fake Resource Models', $tenantResource['name']);
        $this->assertSame($model::class, $environmentResource['model_type']);
        $this->assertSame('Environment Tests Fake Resource Models', $environmentResource['name']);
    }

    public function test_resource_registry_rejects_conflicting_resource_keys_per_scope(): void
    {
        ResourceRegistry::register([
            [
                'key' => 'tests.registry.conflict',
                'name' => 'First resource registration',
                'model_type' => User::class,
                'type' => 'tenant',
            ],
        ], 'tests/package-a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Resource key "tests.registry.conflict" for type "tenant" is already registered by "tests/package-a"');

        ResourceRegistry::register([
            [
                'key' => 'tests.registry.conflict',
                'name' => 'Second resource registration',
                'model_type' => Model::class,
                'type' => 'tenant',
            ],
        ], 'tests/package-b');
    }

    public function test_package_model_resources_are_seeded_automatically(): void
    {
        $this->assertDatabaseHas('resources', [
            'key' => 'nodes',
            'type' => 'tenant',
            'model_type' => Node::class,
        ]);

        $this->assertDatabaseHas('resources', [
            'key' => 'users',
            'type' => 'tenant',
            'model_type' => User::class,
        ]);

        $this->assertDatabaseHas('resources', [
            'key' => 'users',
            'type' => 'environment',
            'model_type' => User::class,
        ]);

    }
}
