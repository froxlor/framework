<?php

namespace Froxlor\Core\Services\Bootstrap;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Setting;
use Illuminate\Support\Facades\Hash;

class BootstrapService
{
    public function initRootTenant(string $email, string $firstName, string $lastName, string $password): User
    {
        // create master tenant
        $tenant = Tenant::query()->create([
            'plan_id' => Plan::query()->where('name', 'Unlimited')->first()->id,
            'name' => 'Froxlor',
            'description' => 'Froxlor Master Tenant'
        ]);

        // let the tenant own the "plans and roles" plan
        Plan::query()->where('name', 'Plans and roles')->first()->update(['tenant_id' => $tenant->id]);

        // create root user
        $user = User::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // 'Super-Admin' role for the users on this tenant
        $superAdminRoleId = Role::query()->where('name', 'Super-Admin')->first()->id;

        // add to tenant
        $user->tenants()->attach($tenant, [
            'role_id' => $superAdminRoleId
        ]);

        // add to 'Super-Admin' globally
        $user->roles()->attach($superAdminRoleId);

        // mark application as initialized
        Setting::add('core.initialized', true, type: 'boolean', properties: ['visible' => false]);

        return $user;
    }
}
