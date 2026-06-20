<?php

namespace Froxlor\Mail\Database\Seeders\Testing;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Support\Setting;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Models\MailAccount;
use Froxlor\Mail\Models\MailAddress;
use Illuminate\Database\Seeder;

class MailTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        $mailAddrResource = Resource::query()->where([
            'key' => 'mailaddresses',
            'type' => 'environment',
            'model_type' => MailAddress::class,
        ])->firstOrFail();
        $mailAccResource = Resource::query()->where([
            'key' => 'mailaccounts',
            'type' => 'environment',
            'model_type' => MailAccount::class,
        ])->firstOrFail();

        // add to environment plans to be available in environment-scoped tests
        foreach (['Environment Unlimited', 'Test Environment Unlimited'] as $planName) {
            $plan = Plan::query()->where('name', $planName)->firstOrFail();
            $plan->resources()->syncWithoutDetaching([
                $mailAddrResource->id => ['limit' => -1],
                $mailAccResource->id => ['limit' => -1],
            ]);
        }

        // introduce our settings
        Setting::add('mail.enabled', true, true, 'boolean');

        // node settings
        Node::addTypeSetting('mail.enabled', false, false, 'boolean');

        /**
         * @todo this is for debugging/development purposes
         */
        $domain = Domain::query()->where('domain', 'example.dev')->first();
        $env = $domain->tenant->environments()->first();
        $node = $env->nodes()->first();
        $domain->update([
            'properties->mail->enabled' => true,
        ]);

        // node specific settings
        $node->addSetting('mail.enabled', true, true, 'boolean');

        $mailAddr = MailAddress::query()->create([
            'domain_id' => $domain->id,
            'address' => 'test@example.dev',
        ]);

    }
}
