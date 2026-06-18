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
        /** @var PackageService $packageService */
        $packageService = app(PackageService::class);

        if (config('dev.repositories')) {
            $packageService->changeToLocalRepository();
        }

        foreach ($this->devPackages() as $package) {
            $packageService->requirePackage($package);
        }
    }

    private function devPackages(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('dev.packages', ''))
        )));
    }
}
