<?php

namespace Froxlor\Core\Database\Seeders\Testing;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Resource;
use Illuminate\Database\Seeder;

class TenantUsagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        /**
         * Main Tenant
         */
        $tenant = Tenant::query()->where('name', 'Froxlor')->first(); // tenant #1
        $tenant2 = Tenant::query()->where('name', 'First customer')->first(); // tenant #2
        $tenant3 = Tenant::query()->where('name', 'Kunde #2')->first(); // tenant #3

        $user1 = User::query()->where('email', config('dev.email'))->first(); // user #1
        $user2 = User::query()->where('email', 'dev2@froxlor.org')->first(); // user #2

        $planUnlimited = Plan::query()->where('name', 'Unlimited')->first();

        Resource::addUsage($tenant, $tenant2, $user1);
        Resource::addUsage($tenant, $planUnlimited, $user1);
        Resource::addUsage($tenant2, $tenant3, $user2);
    }
}
