<?php

namespace Froxlor\Packages\Database\Seeders\Testing;

use Froxlor\Packages\Models\Repository;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Database\Seeder;

class RepositoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (config('dev.repositories')) {
            app(PackageService::class)->changeToLocalRepository();
        }
    }
}
