<?php

namespace Froxlor\Web\Database\Seeders\Testing;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Setting;
use Froxlor\Domain\Models\Domain;
use Froxlor\Web\Models\DomainVhost;
use Illuminate\Database\Seeder;

class WebTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        // introduce our settings
        Setting::add('web.enabled', true, true, 'boolean');
        Setting::add('web.default_vhost_content', '');

        Setting::add('web.ssl_enabled', true, true, 'boolean', ['requires' => ['web.enabled' => true]]);
        Setting::add('web.default_ssl_vhost_content', '');
        Setting::add('web.http2_enabled', true, true, 'boolean');
        Setting::add('web.http3_enabled', false, false, 'boolean');
        Setting::add('web.hsts_maxage', 10368000, 10368000, 'number', ['min' => 0, 'steps' => 1]);
        Setting::add('web.ssl_protocols', 'TLSv1.2', 'TLSv1.2');
        Setting::add('web.ssl_cipher_list', 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:DHE-RSA-CHACHA20-POLY1305');

        // node settings
        Node::addTypeSetting('web.enabled', false, false, 'boolean');
        Node::addTypeSetting('web.httpd', 'apache', 'apache', 'select', ['options' => ['apache', 'nginx']]);

        /**
         * @todo this is for debugging/development purposes
         */
        $domain = Domain::query()->where('domain', 'example.dev')->first();
        $env = $domain->tenant->environments()->first();
        $node = $env->nodes()->first();
        $domain->update([
            'environment_id' => $env->id,
            'node_id' => $node->id,
            'properties->web->enabled' => true,
        ]);

        // node specific settings
        $node->addSetting('web.enabled', true, true, 'boolean');

        $domainVhost = DomainVhost::query()->create([
            'domain_id' => $domain->id,
            'node_id' => $node->id,
            'documentroot' => $node->getSetting('node.basedir') . '/' . $domain->environment->id . '/home/wwwroot/' . $domain->domain,
            'alias_mode' => 'none'
        ]);

        foreach ($node->nodeInterfaces as $interface) {
            $domainVhost->nodeInterfaces()->attach(
                [$interface->id],
                ['port' => 80]
            );
            $domainVhost->nodeInterfaces()->attach(
                [$interface->id],
                ['port' => 443, 'ssl_port' => true]
            );
        }
    }
}
