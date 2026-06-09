<?php

namespace Froxlor\Packages\Database\Seeders;

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
        $this->call($this->seederClasses());
        Audit::log('The package seeder classes have been seeded.');

        // call development seeder classes
        if (App::environment() === 'local') {
            $this->call($this->testingSeederClasses());
            Audit::log('The development package seeder classes have been seeded.');
        }
    }

    private function seederClasses(): array
    {
        return [
            RepositoriesTableSeeder::class,
        ];
    }

    private function testingSeederClasses(): array
    {
        return [
            Testing\RepositoriesTableSeeder::class,
        ];
    }
}
