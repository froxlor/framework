<?php

namespace Froxlor\Packages\Database\Seeders;

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
        foreach ($this->repositories() as $repository) {
            Repository::query()->create($repository);
        }
    }

    protected function repositories(): array
    {
        $token = config('packages.token');

        return [
            [
                'name' => 'froxlor',
                'type' => 'composer',
                'enabled' => true,
                'url' => 'https://packages.froxlor.org',
                'auth' => $token ? [
                    'http-basic' => [
                        'packages.froxlor.org' => [
                            'username' => 'developers',
                            'password' => $token
                        ]
                    ]
                ] : null
            ]
        ];
    }
}
