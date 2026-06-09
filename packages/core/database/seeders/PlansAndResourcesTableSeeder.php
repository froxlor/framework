<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Database\Seeder;

class PlansAndResourcesTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // id=1 | everything unlimited
        self::createPlanWithResources('Unlimited', [
            ['key' => 'tenants', 'name' => 'Tenants', 'type' => 'tenant', 'model_type' => Tenant::class, 'limit' => -1],
            ['key' => 'environments', 'name' => 'User environments', 'type' => 'tenant', 'model_type' => Environment::class, 'limit' => -1],
            ['key' => 'plans', 'name' => 'Usage plans', 'type' => 'tenant', 'model_type' => Plan::class, 'limit' => -1],
            ['key' => 'users', 'name' => 'Tenant Users', 'type' => 'tenant', 'model_type' => User::class, 'limit' => -1],
            ['key' => 'users', 'name' => 'Environment Users', 'type' => 'environment', 'model_type' => User::class, 'limit' => -1],
            ['key' => 'roles', 'name' => 'User roles', 'type' => 'tenant', 'model_type' => Role::class, 'limit' => -1],
        ]);

        // id=2 | everything 10x allowed
        self::createPlanWithResources('Everything 10', [
            ['key' => 'tenants', 'name' => 'Tenants', 'type' => 'tenant', 'model_type' => Tenant::class, 'limit' => 10],
            ['key' => 'environments', 'name' => 'User environments', 'type' => 'tenant', 'model_type' => Environment::class, 'limit' => 10],
            ['key' => 'plans', 'name' => 'Usage plans', 'type' => 'tenant', 'model_type' => Plan::class, 'limit' => 10],
            ['key' => 'users', 'name' => 'Tenant Users', 'type' => 'tenant', 'model_type' => User::class, 'limit' => 10],
            ['key' => 'users', 'name' => 'Environment Users', 'type' => 'environment', 'model_type' => User::class, 'limit' => 10],
            ['key' => 'roles', 'name' => 'User roles', 'type' => 'tenant', 'model_type' => Role::class, 'limit' => 10],
        ]);

        // id=3 | only plans and roles
        self::createPlanWithResources('Plans and roles', [
            ['key' => 'plans', 'name' => 'Usage plans', 'type' => 'tenant', 'model_type' => Plan::class, 'limit' => -1],
            ['key' => 'roles', 'name' => 'User roles', 'type' => 'tenant', 'model_type' => Role::class, 'limit' => 5],
            ['key' => 'test-resource', 'name' => 'Some resource later', 'type' => 'environment', 'model_type' => User::class, 'limit' => 10],
        ]);
    }

    public static function createPlanWithResources(string $string, array $resources, ?string $tenant_id = null): Plan
    {
        /** @var Plan $role */
        $plan = Plan::query()->create([
            'name' => $string,
            'tenant_id' => !empty($tenant_id) ? $tenant_id : null,
        ]);

        foreach ($resources as $resource) {
            $new_resource = Resource::query()->where('key', $resource['key'])->firstOrCreate([
                'key' => $resource['key'],
                'name' => $resource['name'],
                'model_type' => $resource['model_type'],
                'type' => $resource['type'] ?? 'environment',
            ]);
            $plan->resources()->attach($new_resource, [
                'limit' => $resource['limit']
            ]);
        }

        return $plan;
    }
}
