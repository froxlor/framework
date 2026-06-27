<?php

namespace Froxlor\Core\Database\Seeders\Testing;

use Froxlor\Core\Database\Seeders\PlansAndResourcesTableSeeder as BasePlansAndResourcesTableSeeder;
use Illuminate\Database\Seeder;

class PlansAndResourcesTableSeeder extends Seeder
{
    /**
     * Seed deterministic plan fixtures used by authorization and resource-limit tests.
     *
     * Production plans stay human-facing and broadly useful. These plans are deliberately
     * explicit and varied so tests can assert finite limits, no-access limits, unlimited
     * limits, and plan-delegation subset rules without depending on product defaults.
     */
    public function run(): void
    {
        $testTenantUnlimited = BasePlansAndResourcesTableSeeder::createTenantPlan('Test Tenant Unlimited', [
            'tenants' => -1,
            'environments' => -1,
            'nodes' => -1,
            'plans' => -1,
            'users' => -1,
            'roles' => -1,
        ]);
        BasePlansAndResourcesTableSeeder::attachEnvironmentResourceLimits($testTenantUnlimited, ['users' => -1]);

        $testTenantLimited = BasePlansAndResourcesTableSeeder::createTenantPlan('Test Tenant Limited', [
            'tenants' => 2,
            'environments' => 2,
            'nodes' => 2,
            'plans' => 2,
            'users' => 2,
            'roles' => 2,
        ]);
        BasePlansAndResourcesTableSeeder::attachEnvironmentResourceLimits($testTenantLimited, ['users' => 2]);

        $testTenantMinimal = BasePlansAndResourcesTableSeeder::createTenantPlan('Test Tenant Minimal', [
            'tenants' => 0,
            'environments' => 1,
            'nodes' => 0,
            'plans' => 0,
            'users' => 1,
            'roles' => 1,
        ]);
        BasePlansAndResourcesTableSeeder::attachEnvironmentResourceLimits($testTenantMinimal, ['users' => 1]);

        $testTenantDelegationParent = BasePlansAndResourcesTableSeeder::createTenantPlan('Test Tenant Delegation Parent', [
            'tenants' => 0,
            'environments' => 5,
            'nodes' => 1,
            'plans' => 3,
            'users' => 5,
            'roles' => 5,
        ]);
        BasePlansAndResourcesTableSeeder::attachEnvironmentResourceLimits($testTenantDelegationParent, ['users' => 5]);

        $testTenantDelegationChild = BasePlansAndResourcesTableSeeder::createTenantPlan('Test Tenant Delegation Child', [
            'tenants' => 0,
            'environments' => 2,
            'nodes' => 0,
            'plans' => 1,
            'users' => 2,
            'roles' => 2,
        ]);
        BasePlansAndResourcesTableSeeder::attachEnvironmentResourceLimits($testTenantDelegationChild, ['users' => 2]);

        BasePlansAndResourcesTableSeeder::createEnvironmentPlan('Test Environment Unlimited', [
            'users' => -1,
        ]);

        BasePlansAndResourcesTableSeeder::createEnvironmentPlan('Test Environment Limited', [
            'users' => 2,
        ]);

        BasePlansAndResourcesTableSeeder::createEnvironmentPlan('Test Environment Minimal', [
            'users' => 1,
        ]);

        BasePlansAndResourcesTableSeeder::createEnvironmentPlan('Test Environment Delegation Parent', [
            'users' => 5,
        ]);

        BasePlansAndResourcesTableSeeder::createEnvironmentPlan('Test Environment Delegation Child', [
            'users' => 2,
        ]);
    }
}
