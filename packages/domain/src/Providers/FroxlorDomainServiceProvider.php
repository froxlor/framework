<?php

namespace Froxlor\Domain\Providers;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models;
use Froxlor\Domain\Resources\Schemas\DomainSchema;
use Froxlor\UI\Pushable\SidebarLink;
use Froxlor\UI\Schemas;
use Froxlor\UI\Support\UI;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class FroxlorDomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Language
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'froxlor-domain');

        // Policies, Events etc. hier registrieren

        Relation::morphMap([
            'domains' => Models\Domain::class,
        ]);

        // Relations
        $this->extendRelations();

        // User Interface
        $this->extendUserInterface();
    }

    public function register(): void
    {
        //
    }

    private function extendRelations(): void
    {
        // model relations
        Tenant::resolveRelationUsing('domains', function (Tenant $tenant) {
            return $tenant->hasMany(Models\Domain::class);
        });
        Environment::resolveRelationUsing('domains', function (Environment $environment) {
            return $environment->hasMany(Models\Domain::class);
        });

        // ui view relations
        $domainIndexSchema = DomainSchema::indexSchema();
        Schemas\Schema::stack('tenants.show.tabs', fn(Tenant $tenant) => Schemas\Components\Tab::make('tenants.show.tabs.domains')
            ->label(trans('froxlor-domain::generic.domains'))
            ->sort(5000)
            ->components([
                \Froxlor\UI\Schemas\Components\Relation::make('tenants.show.relations.domains')
                    ->fetch(route('api.tenants.domains.index', $tenant))
                    ->intendedRoute('tenants.domains.show', ['tenant' => $tenant->id, 'domain' => '{id}'])
                    ->columns($domainIndexSchema['columns'])
                    ->actions($domainIndexSchema['actions'])
            ])
        );
        Schemas\Schema::stack('tenants.environments.show.tabs', fn(Tenant $tenant, Environment $environment) => Schemas\Components\Tab::make('tenants.environments.show.tabs.domains')
            ->label(trans('froxlor-domain::generic.domains'))
            ->sort(5000)
            ->components([
                Schemas\Components\Relation::make('tenants.environments.show.relations.domains')
                    ->fetch(route('api.tenants.environments.domains.index', [$tenant, $environment]))
                    ->intendedRoute('tenants.environments.domains.show', ['tenant' => $tenant->id, 'environment' => $environment->id, 'domain' => '{id}'])
                    ->columns($domainIndexSchema['columns'])
                    ->actions($domainIndexSchema['actions'])
            ])
        );
    }

    private function extendUserInterface(): void
    {
        $tenantId = fn() => ($tenant = request()->route('tenant') ?? request()->query('tenant'))
            ? ($tenant instanceof Tenant ? $tenant->id : $tenant)
            : null;

        UI::push('sub-sidebar', items: [
            SidebarLink::make('domains')
                ->label(trans('froxlor-domain::generic.domains'))
                ->route(fn() => route('resources.domains.index'))
                ->active(fn() => request()->routeIs('resources.domains.*'))
                ->visible(fn() => request()->routeIs('resources.*'))
                ->icon('globe'),
        ]);

        UI::push('tenant-sidebar', items: [
            SidebarLink::make('tenant-domains', 20)
                ->label(trans('froxlor-domain::generic.domains'))
                ->route(fn() => $tenantId() ? route('tenants.domains.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.domains.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('globe'),
        ]);
    }
}
