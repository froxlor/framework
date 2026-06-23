<?php

namespace Froxlor\Core\Database\Seeders\Testing;

use Froxlor\Core\Support\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the complete core fixture graph used by local development and tests.
     *
     * These seeders intentionally create stable users, tenants, environments, and usage
     * records. Feature tests rely on those fixed actors to exercise cross-tenant access,
     * role assignment, resource limits, and environment-scoped authorization.
     */
    public function run(): void
    {
        Setting::set('auditlog.severity', 7, 'integer', 5);

        $this->call([
            PlansAndResourcesTableSeeder::class,
            TenantAndUsersTableSeeder::class,
            TenantAndEnvironmentsTableSeeder::class,
            TenantUsagesTableSeeder::class,
        ]);
    }
}
