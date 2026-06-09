<?php

namespace Froxlor\Web\Providers;

use Froxlor\Core\Models\NodeInterface;
use Froxlor\Core\Services\Node\Provisioning\ScriptDefinition;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Domain\Models\Domain;
use Froxlor\Web\Models;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;

class FroxlorWebServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('froxlor', fn() => ['web' => '3.0.0']);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-web');

        // Policies, Events etc. hier registrieren

        Relation::morphMap([
            'domain_vhosts' => Models\DomainVhost::class,
        ]);

        // Relations
        $this->extendRelations();

        // Platform-specific provisioning scripts
        $this->registerScripts();

        // Cli commands
        $this->loadCommandsFrom(__DIR__ . '/../Console');
    }

    public function register(): void
    {
        //
    }

    private function extendRelations(): void
    {
        Domain::resolveRelationUsing('domain_vhost', function (Domain $domain) {
            return $domain->hasOne(Models\DomainVhost::class);
        });
        NodeInterface::resolveRelationUsing('domain_vhosts', function (NodeInterface $node) {
            return $node->belongsToMany(Models\DomainVhost::class,
                'domain_vhosts_node_interfaces',
                'node_interface_id',
                'domain_vhost_id')
                ->withPivot(['port', 'ssl_port'])
                ->using(Models\DomainVhostsNodeInterfaces::class);
        });
    }

    private function registerScripts(): void
    {
        ScriptRegistry::register(new ScriptDefinition(
            feature: 'web-vhost',
            action: 'configure',
            platformKey: 'debian@13',
            view: 'froxlor-web::scripts.web-vhost.configure.apache.debian13',
            variant: 'apache',
            targetPath: fn($domainVhost) => '/etc/apache2/sites-available/' . $domainVhost->domain->domain . '.conf',
            runAsRoot: true,
            ownership: ['root', 'root'],
            reloadCommands: fn($domainVhost) => [
                'apachectl configtest',
                'a2ensite ' . escapeshellarg($domainVhost->domain->domain . '.conf'),
                'systemctl reload apache2',
            ],
            package: 'web',
        ));

        ScriptRegistry::register(new ScriptDefinition(
            feature: 'web-vhost',
            action: 'configure',
            platformKey: 'debian@13',
            view: 'froxlor-web::scripts.web-vhost.configure.nginx.debian13',
            variant: 'nginx',
            targetPath: fn($domainVhost) => '/etc/nginx/sites-available/' . $domainVhost->domain->domain . '.conf',
            runAsRoot: true,
            ownership: ['root', 'root'],
            reloadCommands: fn($domainVhost) => [
                'nginx -t',
                'ln -sf ' . escapeshellarg('/etc/nginx/sites-available/' . $domainVhost->domain->domain . '.conf')
                    . ' '
                    . escapeshellarg('/etc/nginx/sites-enabled/' . $domainVhost->domain->domain . '.conf'),
                'systemctl reload nginx',
            ],
            package: 'web',
        ));

        ScriptRegistry::register(new ScriptDefinition(
            feature: 'web-vhost',
            action: 'configure',
            platformKey: 'ubuntu@24.04',
            view: 'froxlor-web::scripts.web-vhost.configure.apache.ubuntu2404',
            variant: 'apache',
            targetPath: fn($domainVhost) => '/etc/apache2/sites-available/' . $domainVhost->domain->domain . '.conf',
            runAsRoot: true,
            ownership: ['root', 'root'],
            reloadCommands: fn($domainVhost) => [
                'apachectl configtest',
                'a2ensite ' . escapeshellarg($domainVhost->domain->domain . '.conf'),
                'systemctl reload apache2',
            ],
            package: 'web',
        ));

        ScriptRegistry::register(new ScriptDefinition(
            feature: 'web-vhost',
            action: 'configure',
            platformKey: 'ubuntu@24.04',
            view: 'froxlor-web::scripts.web-vhost.configure.nginx.ubuntu2404',
            variant: 'nginx',
            targetPath: fn($domainVhost) => '/etc/nginx/sites-available/' . $domainVhost->domain->domain . '.conf',
            runAsRoot: true,
            ownership: ['root', 'root'],
            reloadCommands: fn($domainVhost) => [
                'nginx -t',
                'ln -sf ' . escapeshellarg('/etc/nginx/sites-available/' . $domainVhost->domain->domain . '.conf')
                    . ' '
                    . escapeshellarg('/etc/nginx/sites-enabled/' . $domainVhost->domain->domain . '.conf'),
                'systemctl reload nginx',
            ],
            package: 'web',
        ));
    }
}
