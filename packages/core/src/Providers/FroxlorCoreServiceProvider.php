<?php

namespace Froxlor\Core\Providers;

use Froxlor\Core\Models;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Policies\TenantPolicy;
use Froxlor\Core\Services\Node\Adapter\Local;
use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\UI\Pushable\SidebarLink;
use Froxlor\UI\Pushable\SidebarTenantLink;
use Froxlor\UI\Support\UI;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use URL;

class FroxlorCoreServiceProvider extends PackageServiceProvider
{
    const HOME = '/';

    public function boot(): void
    {
        AboutCommand::add('froxlor', fn() => [
            'version' => FroxlorVersion::release(),
        ]);

        AboutCommand::add('froxlor packages', fn() => [
            'core' => FroxlorVersion::installedApplicationVersion('froxlor/core', FroxlorVersion::release())
        ]);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-core');

        // Language
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'froxlor-core');

        // Blade components
        Blade::componentNamespace('Froxlor\\Core\\Views\\Components', 'froxlor-core');

        // Cli commands
        $this->loadCommandsFrom(__DIR__ . '/../Console');

        Gate::policy(Tenant::class, TenantPolicy::class);

        // User Interface
        $this->extendUserInterface();

        Relation::morphMap([
            'environments' => Models\Environment::class,
            'nodes' => Models\Node::class,
            'permissions' => Models\Permission::class,
            'plans' => Models\Plan::class,
            'resources' => Models\Resource::class,
            'roles' => Models\Role::class,
            'tenants' => Models\Tenant::class,
            'users' => Models\User::class,
        ]);

        // Adapters
        Models\Node::registerAdapter(Local::class);

        // Schedule
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('core:explore-node')->everyFiveMinutes();
        });

        // Ensure HTTPS when forced
        if (env('FORCE_HTTPS', false)) {
            $this->app['request']->server->set('HTTPS', true);
            $this->app['request']->server->set('SERVER_PORT', 443);

            URL::forceScheme('https');
        }
    }

    public function register(): void
    {
        // Configs
        $this->mergeConfigFrom(__DIR__ . '/../../config/dev.php', 'dev');
    }

    private function extendUserInterface(): void
    {
        $tenantId = fn() => ($tenant = request()->route('tenant') ?? request()->query('tenant'))
            ? ($tenant instanceof Models\Tenant ? $tenant->id : $tenant)
            : null;

        UI::push('sidebar', items: [
            SidebarLink::make('dashboard')
                ->label(trans('froxlor-core::generic.dashboard'))
                ->route(fn() => route('overview'))
                ->active(fn() => request()->routeIs('overview'))
                ->icon('house'),
        ]);

        UI::push('sidebar-footer', items: [
            SidebarLink::make('authentication')
                ->label(trans('froxlor-core::generic.authentication'))
                ->route(fn() => route('auth.users.index'))
                ->active(fn() => request()->routeIs('auth.*'))
                ->icon('shield'),

            SidebarLink::make('resources')
                ->label(trans('froxlor-core::generic.resources'))
                ->route(fn() => route('resources.nodes.index'))
                ->active(fn() => request()->routeIs('resources.*'))
                ->icon('list-todo'),

            SidebarLink::make('audit-log')
                ->label(trans('froxlor-core::generic.audit-log'))
                ->route(fn() => route('audit-log.index'))
                ->active(fn() => request()->routeIs('audit-log.*'))
                ->icon('file-clock'),

            SidebarLink::make('settings')
                ->label(trans('froxlor-core::generic.settings'))
                ->route(fn() => route('settings.index'))
                ->active(fn() => request()->routeIs('settings.*'))
                ->icon('settings'),
        ]);

        UI::push('sub-sidebar', items: [
            SidebarLink::make('nodes')
                ->label(fn() => Node::adapters() !== [Local::class]
                    ? trans('froxlor-core::generic.nodes')
                    : trans('froxlor-core::generic.node'))
                ->route(fn() => route('resources.nodes.index'))
                ->active(fn() => request()->routeIs('resources.nodes.*'))
                ->visible(fn() => request()->routeIs('resources.*'))
                ->icon('hard-drive'),

            SidebarLink::make('plans')
                ->label(trans('froxlor-core::generic.plans'))
                ->route(fn() => route('resources.plans.index'))
                ->active(fn() => request()->routeIs('resources.plans.*'))
                ->visible(fn() => request()->routeIs('resources.*'))
                ->icon('receipt-text'),

            SidebarLink::make('tenants')
                ->label(trans('froxlor-core::generic.tenants'))
                ->route(fn() => route('resources.tenants.index'))
                ->active(fn() => request()->routeIs('resources.tenants.*'))
                ->visible(fn() => request()->routeIs('resources.*'))
                ->icon('square-library'),
        ]);

        UI::push('sub-sidebar', items: [
            SidebarLink::make('users')
                ->label(trans('froxlor-core::generic.users'))
                ->route(fn() => route('auth.users.index'))
                ->active(fn() => request()->routeIs('auth.users.*'))
                ->visible(fn() => fn() => request()->routeIs('auth.*'))
                ->icon('users'),

            SidebarLink::make('api-keys')
                ->label(trans('froxlor-core::generic.api_keys'))
                ->route(fn() => route('auth.api-keys.index'))
                ->active(fn() => request()->routeIs('auth.api-keys.*'))
                ->visible(fn() => request()->routeIs('auth.*'))
                ->icon('key'),

            SidebarLink::make('roles')
                ->label(trans('froxlor-core::generic.roles'))
                ->route(fn() => route('auth.roles.index'))
                ->active(fn() => request()->routeIs('auth.roles.*'))
                ->visible(fn() => request()->routeIs('auth.*'))
                ->icon('folder-key'),
        ]);

        UI::push('tenant-sidebar', items: [
            SidebarTenantLink::make('tenant-overview', 0)
                ->label(trans('froxlor-core::generic.overview'))
                ->route(fn() => $tenantId() ? route('tenants.show', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.show') && !request()->filled('tab'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('layout-dashboard'),

            SidebarTenantLink::make('tenant-environments', 10)
                ->label(trans('froxlor-core::generic.environments'))
                ->route(fn() => $tenantId() ? route('tenants.environments.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.environments.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('boxes'),

            SidebarTenantLink::make('tenant-plans', 30)
                ->label(trans('froxlor-core::generic.plans'))
                ->route(fn() => $tenantId() ? route('tenants.plans.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.plans.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('receipt-text'),

            SidebarTenantLink::make('tenant-roles', 40)
                ->label(trans('froxlor-core::generic.roles'))
                ->route(fn() => $tenantId() ? route('tenants.roles.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.roles.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('folder-key'),

            SidebarTenantLink::make('tenant-users', 50)
                ->label(trans('froxlor-core::generic.users'))
                ->route(fn() => $tenantId() ? route('tenants.users.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.users.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('users'),

            SidebarTenantLink::make('tenant-audit-log', 60)
                ->label(trans('froxlor-core::generic.audit-log'))
                ->route(fn() => $tenantId() ? route('tenants.audit-log.index', ['tenant' => $tenantId()]) : '#')
                ->active(fn() => request()->routeIs('tenants.audit-log.*'))
                ->visible(fn() => (bool)$tenantId())
                ->icon('file-clock'),
        ]);
    }
}
