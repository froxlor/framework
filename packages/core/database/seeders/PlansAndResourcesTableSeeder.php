<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\ResourceRegistry;
use Illuminate\Database\Seeder;

class PlansAndResourcesTableSeeder extends Seeder
{
    /**
     * Seed the baseline resource catalog and global production plans.
     *
     * Tenant plans only contain tenant-scope resources. Environment plans only contain
     * environment-scope resources. Limit semantics are `0` for no access, `-1` for
     * unlimited, and positive values for finite limits.
     */
    public function run(): void
    {
        self::seedResourceCatalog();

        self::createTenantPlan('Platform Unlimited', [
            'tenants' => -1,
            'environments' => -1,
            'nodes' => -1,
            'plans' => -1,
            'users' => -1,
            'roles' => -1,
        ]);

        self::createTenantPlan('Tenant Standard', [
            'tenants' => 0,
            'environments' => 10,
            'nodes' => 0,
            'plans' => 5,
            'users' => 25,
            'roles' => 10,
        ]);

        self::createTenantPlan('Tenant Starter', [
            'tenants' => 0,
            'environments' => 1,
            'nodes' => 0,
            'plans' => 0,
            'users' => 3,
            'roles' => 3,
        ]);

        self::createEnvironmentPlan('Environment Unlimited', [
            'users' => -1,
        ]);

        self::createEnvironmentPlan('Environment Standard', [
            'users' => 10,
        ]);

        self::createEnvironmentPlan('Environment Starter', [
            'users' => 2,
        ]);
    }

    /**
     * Seed all core resources once so plans can reuse stable resource definitions.
     */
    public static function seedResourceCatalog(): void
    {
        ResourceRegistry::registerPackageModelsFrom(dirname(__DIR__, 3));
        ResourceRegistry::sync();
    }

    /**
     * Create or update a global or tenant-owned tenant-scope plan.
     *
     * @param array<string, int> $limits Resource key to limit map.
     */
    public static function createTenantPlan(string $name, array $limits, ?string $tenantId = null): Plan
    {
        return self::createPlanWithResourceLimits($name, $limits, $tenantId, 'tenant');
    }

    /**
     * Create or update a global or tenant-owned environment-scope plan.
     *
     * @param array<string, int> $limits Resource key to limit map.
     */
    public static function createEnvironmentPlan(string $name, array $limits, ?string $tenantId = null): Plan
    {
        return self::createPlanWithResourceLimits($name, $limits, $tenantId, 'environment');
    }

    /**
     * Create a plan and attach resource limits matching the plan scope.
     *
     * @param array<string, int> $limits Resource key to limit map.
     */
    private static function createPlanWithResourceLimits(string $name, array $limits, ?string $tenantId = null, ?string $resourceType = null): Plan
    {
        /** @var Plan $plan */
        $plan = Plan::query()->updateOrCreate([
            'tenant_id' => $tenantId,
            'name' => $name,
        ], [
            'description' => null,
        ]);

        $resources = match ($resourceType) {
            'tenant' => self::tenantResources(),
            'environment' => self::environmentResources(),
            default => array_merge(self::tenantResources(), self::environmentResources()),
        };

        foreach ($limits as $key => $limit) {
            if (!isset($resources[$key])) {
                throw new \InvalidArgumentException('Unknown resource key "' . $key . '" for plan "' . $name . '".');
            }

            $plan->resources()->syncWithoutDetaching([
                self::resource($resources[$key])->id => ['limit' => $limit],
            ]);
        }

        return $plan->refresh();
    }

    /**
     * Return tenant-scope core resource definitions.
     *
     * @return array<string, array{key: string, name: string, type: string, model_type: class-string}>
     */
    private static function tenantResources(): array
    {
        return [
            'tenants' => ['key' => 'tenants', 'name' => 'Tenants', 'type' => 'tenant', 'model_type' => Tenant::class],
            'environments' => ['key' => 'environments', 'name' => 'Environments', 'type' => 'tenant', 'model_type' => Environment::class],
            'nodes' => ['key' => 'nodes', 'name' => 'Nodes', 'type' => 'tenant', 'model_type' => Node::class],
            'plans' => ['key' => 'plans', 'name' => 'Plans', 'type' => 'tenant', 'model_type' => Plan::class],
            'users' => ['key' => 'users', 'name' => 'Tenant users', 'type' => 'tenant', 'model_type' => User::class],
            'roles' => ['key' => 'roles', 'name' => 'Roles', 'type' => 'tenant', 'model_type' => Role::class],
        ];
    }

    /**
     * Return environment-scope core resource definitions.
     *
     * @return array<string, array{key: string, name: string, type: string, model_type: class-string}>
     */
    private static function environmentResources(): array
    {
        return [
            'users' => ['key' => 'users', 'name' => 'Environment users', 'type' => 'environment', 'model_type' => User::class],
        ];
    }

    /**
     * Create or update one resource definition.
     *
     * @param array{key: string, name: string, type: string, model_type: class-string} $resource
     */
    private static function resource(array $resource): Resource
    {
        /** @var Resource $model */
        $model = Resource::query()->where([
            'key' => $resource['key'],
            'model_type' => $resource['model_type'],
            'type' => $resource['type'],
        ])->firstOrFail();

        return $model;
    }
}
