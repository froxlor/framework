<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Core\Support\Audit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // call required seeder classes
        $this->call($this->coreSeederClasses());
        Audit::log('The core seeder classes have been seeded.');

        // call development seeder classes
        if (App::environment() === 'local') {
            $this->call($this->testingSeederClasses());
            Audit::log('The development core seeder classes have been seeded.');
        }

        // notify other seeders
        event(new DatabaseSeeded($this));
    }

    /**
     * All essential seeders for a minimal installation.
     */
    private function coreSeederClasses(): array
    {
        return [
            SettingsTableSeeder::class,
            NodesTableSeeder::class,
            RolesAndPermissionsTableSeeder::class,
            PlansAndResourcesTableSeeder::class,
        ];
    }

    /**
     * All seeders for test data and local development.
     */
    private function testingSeederClasses(): array
    {
        return [
            Testing\TenantAndUsersTableSeeder::class,
            Testing\TenantAndEnvironmentsTableSeeder::class,
            Testing\TenantUsagesTableSeeder::class,
            // add more development resources connected to environments later here
        ];
    }
}
