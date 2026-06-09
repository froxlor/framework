<?php

namespace Froxlor\Ftp\Providers;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Provisioning\ScriptDefinition;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Ftp\Models\FtpService;
use Froxlor\Ftp\Resources\Nodes\FtpServiceResource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;

class FroxlorFtpServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-ftp');

        // Relations
        $this->extendRelations();

        // Node UI extensions
        $this->extendUserInterface();

        // Platform-specific provisioning scripts
        $this->registerScripts();
    }

    public function register(): void
    {
        //
    }

    private function extendRelations(): void
    {
        Node::resolveRelationUsing('ftpService', function (Node $node) {
            return $node->hasOne(FtpService::class);
        });
    }

    private function extendUserInterface(): void
    {
        Schema::stack('resources.nodes.show.tabs', function (Node $node) {
            return Schemas\Components\Tab::make('resources.nodes.show.tabs.ftp_service')
                ->sort(1600)
                ->label('FTP service')
                ->components([
                    $node->ftpService
                        ? app(FtpServiceResource::class)->show($node)
                        : app(FtpServiceResource::class)->create($node),
                ]);
        });
    }

    private function registerScripts(): void
    {
        foreach (['debian13' => 'debian@13', 'ubuntu2404' => 'ubuntu@24.04'] as $slug => $platformKey) {
            ScriptRegistry::register(new ScriptDefinition(
                feature: 'ftp-service',
                action: 'install',
                platformKey: $platformKey,
                view: "froxlor-ftp::scripts.ftp-service.install.vsftpd.{$slug}",
                variant: 'vsftpd',
                targetPath: '/usr/local/lib/froxlor/ftp-service/install-vsftpd.sh',
                runAsRoot: true,
                ownership: ['root', 'root'],
                executable: true,
                executeAfterWrite: true,
                package: 'ftp',
            ));

            ScriptRegistry::register(new ScriptDefinition(
                feature: 'ftp-service',
                action: 'configure',
                platformKey: $platformKey,
                view: "froxlor-ftp::scripts.ftp-service.configure.vsftpd.{$slug}",
                variant: 'vsftpd',
                targetPath: '/usr/local/lib/froxlor/ftp-service/configure-vsftpd.sh',
                runAsRoot: true,
                ownership: ['root', 'root'],
                executable: true,
                executeAfterWrite: true,
                reloadCommands: [
                    'vsftpd -version || true',
                    'systemctl restart vsftpd',
                    'systemctl is-active vsftpd',
                ],
                package: 'ftp',
            ));
        }
    }
}
