<?php

namespace Froxlor\Database\Providers;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Provisioning\ScriptDefinition;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Database\Models\Database;
use Froxlor\Database\Models\DatabaseServer;
use Froxlor\Database\Resources\Nodes\DatabaseServiceResource;
use Froxlor\Database\Resources\Tenants\Relations\Databases\Tables\DatabaseTable;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;

class FroxlorDatabaseServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-database');

        // Relations
        $this->extendRelations();

        // Tenant environment UI extensions
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
        Environment::resolveRelationUsing('databases', function (Environment $environment) {
            return $environment->hasMany(Database::class);
        });
        Node::resolveRelationUsing('databaseServer', function (Node $node) {
            return $node->hasOne(DatabaseServer::class);
        });
    }

    private function extendUserInterface(): void
    {
        Schema::stack('tenants.environments.show.tabs', function (Environment $environment) {
            return Schemas\Components\Tab::make('tenants.environments.show.tabs.databases')
                ->sort(1500)
                ->label('Databases')
                ->components([
                    Schemas\Components\Relation::make('databases')
                        ->fetch(route('api.tenants.environments.databases.index', [
                            'tenant' => $environment->tenant_id,
                            'environment' => $environment,
                        ]))
                        ->intendedRoute('tenants.environments.databases.show', [
                            'tenant' => $environment->tenant_id,
                            'environment' => $environment->id,
                            'database' => '{id}',
                        ])
                        ->columns(DatabaseTable::columns())
                        ->columnActions(DatabaseTable::columnActions($environment))
                        ->actions(DatabaseTable::actions($environment)),
                ]);
        });

        Schema::stack('resources.nodes.show.tabs', function (Node $node) {
            return Schemas\Components\Tab::make('resources.nodes.show.tabs.database_service')
                ->sort(1500)
                ->label('Database service')
                ->components([
                    $node->databaseServer
                        ? app(DatabaseServiceResource::class)->show($node)
                        : app(DatabaseServiceResource::class)->create($node),
                ]);
        });
    }

    private function registerScripts(): void
    {
        foreach (['debian13' => 'debian@13', 'ubuntu2404' => 'ubuntu@24.04'] as $slug => $platformKey) {
            foreach (['mariadb', 'mysql', 'pgsql'] as $driver) {
                ScriptRegistry::register(new ScriptDefinition(
                    feature: 'database-service',
                    action: 'install',
                    platformKey: $platformKey,
                    view: "froxlor-database::scripts.database-service.install.{$driver}.{$slug}",
                    variant: $driver,
                    targetPath: "/usr/local/lib/froxlor/database-service/install-{$driver}.sh",
                    runAsRoot: true,
                    ownership: ['root', 'root'],
                    executable: true,
                    executeAfterWrite: true,
                    package: 'database',
                ));

                ScriptRegistry::register(new ScriptDefinition(
                    feature: 'database-service',
                    action: 'configure',
                    platformKey: $platformKey,
                    view: "froxlor-database::scripts.database-service.configure.{$driver}.{$slug}",
                    variant: $driver,
                    targetPath: "/usr/local/lib/froxlor/database-service/configure-{$driver}.sh",
                    runAsRoot: true,
                    ownership: ['root', 'root'],
                    executable: true,
                    executeAfterWrite: true,
                    reloadCommands: $this->reloadCommands($driver),
                    package: 'database',
                ));
            }
        }
    }

    private function reloadCommands(string $driver): array
    {
        return match ($driver) {
            'pgsql' => [
                'psql --version',
                'systemctl restart postgresql',
                'systemctl is-active postgresql',
            ],
            'mysql' => [
                'mysql --version',
                'systemctl restart mysql || systemctl restart mysqld',
                'systemctl is-active mysql || systemctl is-active mysqld',
            ],
            default => [
                'mariadb --version || mysql --version',
                'systemctl restart mariadb',
                'systemctl is-active mariadb',
            ],
        };
    }
}
