<?php

namespace Froxlor\Packages\Providers;

use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Packages\Services\PackageService;
use Froxlor\UI\Pushable\NavbarLink;
use Froxlor\UI\Pushable\SidebarLink;
use Froxlor\UI\Support\UI;
use Illuminate\Support\Facades\Blade;

class FroxlorPackageServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../../config/packages.php' => config_path('packages.php'),
        ], 'packages-config');

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-packages');

        // Language
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'froxlor-packages');

        // Blade components
        Blade::componentNamespace('Froxlor\\Packages\\Views\\Components', 'froxlor-packages');

        // Cli commands
        $this->loadCommandsFrom(__DIR__ . '/../Console');

        // User Interface
        $this->extendUserInterface();
    }

    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../../config/packages.php', 'packages');
    }

    private function extendUserInterface(): void
    {
        UI::push('primary', items: [
            NavbarLink::make('version')
                ->label(trans('froxlor-packages::generic.update_available'))
                ->route(fn() => route('packages.updater.index'))
                ->active(fn() => request()->routeIs('packages.*'))
                ->visible(fn() => app(PackageService::class)->hasAvailableUpdates())
                ->icon('package-2'),
        ]);

        UI::push('sidebar-footer', items: [
            SidebarLink::make('packages')
                ->label(trans('froxlor-packages::generic.packages'))
                ->route(fn() => route('packages.discovery.index'))
                ->active(fn() => request()->routeIs('packages.*'))
                ->icon('package-2'),
        ]);

        UI::push('sub-sidebar', items: [
            SidebarLink::make('discovery')
                ->label(trans('froxlor-packages::generic.discovery'))
                ->route(fn() => route('packages.discovery.index'))
                ->active(fn() => request()->routeIs('packages.discovery.*'))
                ->visible(fn() => request()->routeIs('packages.*'))
                ->icon('sparkles'),

            SidebarLink::make('index')
                ->label(trans('froxlor-packages::generic.installed_packages'))
                ->route(fn() => route('packages.index'))
                ->active(fn() => request()->routeIs('packages.index'))
                ->visible(fn() => request()->routeIs('packages.*'))
                ->icon('package-2'),

            SidebarLink::make('repositories')
                ->label(trans('froxlor-packages::generic.repositories'))
                ->route(fn() => route('packages.repositories.index'))
                ->active(fn() => request()->routeIs('packages.repositories.*'))
                ->visible(fn() => request()->routeIs('packages.*'))
                ->icon('cloud-download'),
        ]);
    }
}
