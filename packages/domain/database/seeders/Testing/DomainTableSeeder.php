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
        // add ourselves as available resource
        $domain_resource = Resource::query()->create([
            'key' => 'domains',
            'name' => 'Domains',
            'type' => 'environment',
            'model_type' => Domain::class,
        ]);

        // add to unlimited plan to be available for super-admin
        $plan = Plan::query()->where('name', 'Unlimited')->first();
        $plan->resources()->attach($domain_resource, [
            'limit' => -1
        ]);

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
