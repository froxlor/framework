<?php

namespace Froxlor\Core\Database\Seeders\Testing;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\User;
use Illuminate\Database\Seeder;

class TenantAndEnvironmentsTableSeeder extends Seeder
{

    /**
     * Seed stable environment fixtures for tenant and environment authorization tests.
     *
     * Each environment is attached to the first node so controller tests can exercise
     * provisioning, node cleanup, environment user permissions, and resource usage logic.
     *
     * @return void
     */
    public function run(): void
    {
        $node = Node::query()->first();
        $user = User::query()->where('email', config('dev.email'))->first(); // user #1
        $env1 = $user->environments()->create([
            'name' => 'Development Environment',
            'tenant_id' => $user->tenants[0]->id,
            'plan_id' => Plan::query()->where('name', 'Test Environment Unlimited')->first()->id
        ], [
            'role_id' => Role::query()->where('name', 'Super-Admin')->first()->id // Super-Admin role for the users on this environment
        ]);

        $this->attachEnvToNode($env1, $node);

        $user2 = User::query()->where('email', 'dev2@froxlor.org')->first(); // user #2
        $env2 = $user2->environments()->create([
            'name' => 'Kunden Environment',
            'tenant_id' => $user2->tenants[0]->id,
            'plan_id' => Plan::query()->where('name', 'Test Environment Limited')->first()->id,
        ], [
            'role_id' => Role::query()->where('name', 'Super-Admin')->first()->id // Super-Admin role for the users on this environment
        ]);
        $this->attachEnvToNode($env2, $node);

        $user3 = User::query()->where('email', 'dev3@froxlor.org')->first(); // user #3
        $env3 = $user3->environments()->create([
            'name' => 'Reseller->User Environment',
            'tenant_id' => $user3->tenants[0]->id,
            'plan_id' => Plan::query()->where('name', 'Test Environment Minimal')->first()->id
        ], [
            'role_id' => Role::query()->where('name', 'Reseller')->first()->id // Reseller role for the users on this environment
        ]);
        $this->attachEnvToNode($env3, $node);
    }

    /**
     * Attach an environment to a node without running provisioning observers.
     */
    private function attachEnvToNode(Environment $env, Node $node): void
    {
        $unixName = $node->latestUnixName;
        $guid = $node->nextGuid;

        // connect environment with node (must be mode=main)
        $node->environments()->attach($env, [
            'unix_name' => $unixName,
            'guid' => $guid,
            'mode' => 'main'
        ]);

        // increment last_username_number and last_guid_number because no observers here
        $node->setSetting('node.last_username_number', ($node->getSetting('node.last_username_number') + 1));
        $node->setSetting('node.last_guid_number', $guid);
    }
}
