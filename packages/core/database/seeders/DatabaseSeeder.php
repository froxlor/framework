<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\SeedProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // call required package seeders
        $this->call($this->coreSeederClasses());
        Audit::log('The core seeder classes have been seeded.');

        // call development/test fixture seeders
        if (SeedProfile::includesDevelopmentData()) {
            $this->call($this->fixtureSeederClasses());
            Audit::log('The ' . SeedProfile::developmentDataLabel() . ' core seeder classes have been seeded.');
        }

        // notify other seeders
        event(new DatabaseSeeded($this));
    }

    /**
     * All essential seeders required for a minimal production installation.
     *
     * @return array<class-string<Seeder>>
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
     * All non-production fixture seeders used by local development and tests.
     *
     * @return array<class-string<Seeder>>
     */
    private function fixtureSeederClasses(): array
    {
        return [
            Testing\DatabaseSeeder::class,
        ];
    }
}
