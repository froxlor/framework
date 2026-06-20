<?php

namespace Froxlor\Domain\Database\Seeders\Testing;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models\Domain;
use Illuminate\Database\Seeder;

class DomainTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        $domainResource = Resource::query()->where([
            'key' => 'domains',
            'type' => 'environment',
            'model_type' => Domain::class,
        ])->firstOrFail();

        // add to environment plans to be available in environment-scoped tests
        foreach (['Environment Unlimited', 'Test Environment Unlimited'] as $planName) {
            $plan = Plan::query()->where('name', $planName)->firstOrFail();
            $plan->resources()->syncWithoutDetaching([
                $domainResource->id => ['limit' => -1],
            ]);
        }

        /**
         * @todo this is for debugging/development purposes
         */
        $tenant = Tenant::query()->first();
        foreach (['dev', 'local', 'test', 'prod', 'tld'] as $domaintld) {
            $tenant->domains()->create([
                'domain' => 'example.'.$domaintld,
            ]);
        }
    }
}
