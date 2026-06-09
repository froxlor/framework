<?php

namespace Froxlor\Core\Database\Seeders\Testing;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Bootstrap\BootstrapService;
use Froxlor\Core\Support\Setting;
use Illuminate\Database\Seeder;

class TenantAndUsersTableSeeder extends Seeder
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
        $user = app(BootstrapService::class)->initRootTenant(
            config('dev.email'),
            config('dev.first_name'),
            config('dev.last_name'),
            config('dev.password'),
        );

        // add two roles owned only by this tenant
        $tenant = $user->tenants()->first();
        $tenantRole = $tenant->roles()->create([
            'name' => 'Custom Tenant-Role #1',
            'tenant_id' => $tenant->id,
        ]);
        $tenantRole2 = $tenant->roles()->create([
            'name' => 'Custom Tenant-Role #2',
            'tenant_id' => $tenant->id,
        ]);

        /**
         * 1st Level Tenant
         */
        $tenant2 = Tenant::query()->create([
            'plan_id' => Plan::query()->where('name', 'Everything 10')->first()->id, // Everything 10
            'name' => 'First customer',
            'parent_tenant_id' => $tenant->id,
        ]);

        /**
         * User #2
         */
        /** @var User $user2 */
        $user2 = User::query()->create([
            'first_name' => 'Customer',
            'last_name' => 'Admin',
            'email' => 'dev2@froxlor.org',
            'password' => bcrypt('DtQOWsmW9eH3rrlA9uujhDmY'),
        ]);
        // add to tenant
        $user2->tenants()->attach($tenant2, [
            'role_id' => Role::query()->where('name', 'Admin')->first()->id // Admin role for the users on this tenant
        ]);

        /**
         * Sub Tenant #2
         */
        $tenant3 = Tenant::query()->create([
            'parent_tenant_id' => $tenant2->id,
            'plan_id' => Plan::query()->where('name', 'Unlimited')->first()->id,
            'name' => 'Kunde #2',
            'description' => 'Another Tenant'
        ]);

        /**
         * User #3
         */
        /** @var User $user3 */
        $user3 = User::query()->create([
            'first_name' => 'Kunden',
            'last_name' => 'Admin',
            'company_name' => 'Kundenfirma',
            'email' => 'dev3@froxlor.org',
            'password' => bcrypt('DtQOWsmW9eH3rrlA9uujhDmY'),
        ]);
        // add to tenant
        $user3->tenants()->attach($tenant3, [
            'role_id' => Role::query()->where('name', 'Super-Admin')->first()->id, // Super-Admin role for the users on this tenant
            'plan_id' => Plan::query()->where('name', 'Everything 10')->first()->id // Everything 10
        ]);
    }

}
