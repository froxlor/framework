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
        // add ourselves as available resource
        $mailAddrResource = Resource::query()->create([
            'key' => 'mailaddresses',
            'name' => 'Mail addresses',
            'type' => 'environment',
            'model_type' => MailAddress::class,
        ]);
        $mailAccResource = Resource::query()->create([
            'key' => 'mailaccounts',
            'name' => 'Mail accounts',
            'type' => 'environment',
            'model_type' => MailAccount::class,
        ]);

        // add to unlimited plan to be available for super-admin
        $plan = Plan::query()->where('name', 'Unlimited')->first();
        $plan->resources()->attach($mailAddrResource, [
            'limit' => -1
        ]);
        $plan->resources()->attach($mailAccResource, [
            'limit' => -1
        ]);

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
