<?php

namespace Froxlor\Web\Database\Seeders;

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
        $this->call($this->seederClasses());
        Audit::log('The web seeder classes have been seeded.');

        // call development/test fixture seeders
        if (SeedProfile::includesDevelopmentData()) {
            $this->call($this->testingSeederClasses());
            Audit::log('The ' . SeedProfile::developmentDataLabel() . ' web seeder classes have been seeded.');
        }
    }

    /**
     * All essential seeders required for a minimal production installation.
     *
     * @return array<class-string<Seeder>>
     */
    private function seederClasses(): array
    {
        return [];
    }

    /**
     * All non-production fixture seeders used by local development and tests.
     *
     * @return array<class-string<Seeder>>
     */
    private function testingSeederClasses(): array
    {
        return [
            Testing\WebTableSeeder::class
        ];
    }
}
