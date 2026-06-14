<?php

namespace Froxlor\UI\Providers;

use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\UI\Pushable\SettingLink;
use Froxlor\UI\Pushable\UserDropdownLink;
use Froxlor\UI\Support\UI;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class FroxlorUIServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('froxlor packages', fn() => [
            'ui' => FroxlorVersion::installedApplicationVersion('froxlor/ui', FroxlorVersion::release())
        ]);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ui');

        // Language
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'froxlor-ui');

        // Blade components
        Blade::componentNamespace('Froxlor\\UI\\View\\Components', 'ui');

        // Livewire
        Livewire::addNamespace(
            namespace: 'ui',
            viewPath: __DIR__ . '/../../resources/views'
        );

        // Public assets
        $this->publishes([__DIR__ . '/../../dist' => public_path('vendor/froxlor/ui')], 'froxlor-ui-assets');

        // Assets
        UI::assetsDirective('vendor/froxlor/ui', [
            'app.js',
            'styles.css',
            'libs/highlight.js/highlight.min.css',
            'libs/highlight.js/highlight.min.js',
            'libs/highlight.js/blade.min.js',
        ]);

        // User Interface
        $this->extendUserInterface();
    }

    public function register(): void
    {
        //
    }

    /**
     * Register navigation items and other UI related stuff.
     */
    private function extendUserInterface(): void
    {
        UI::push('sidebar');

        UI::push('settings', items: [
            SettingLink::make('appearance')
                ->label(trans('froxlor-ui::generic.appearance'))
                ->route(fn() => route('ui.appearance.index'))
                ->icon('paint-bucket'),
        ]);

        UI::push('user', items: [
            UserDropdownLink::make('tenant')
                ->label('Tenant')
                ->route(function () {
                    $tenant = request()->route('tenant') ?? request()->query('tenant');
                    $tenantId = $tenant instanceof \Froxlor\Core\Models\Tenant ? $tenant->id : $tenant;
                    if ($tenantId) {
                        return route('tenants.show', ['tenant' => $tenantId, 'nav' => 'tenant']);
                    }

                    return route('resources.tenants.index', ['nav' => 'tenant']);
                }),

            UserDropdownLink::make('profile')
                ->label(trans('froxlor-core::generic.profile'))
                ->route(fn() => '#'),

            UserDropdownLink::make('settings')
                ->label(trans('froxlor-core::generic.settings'))
                ->route(fn() => route('settings.index')),
        ]);

        UI::push('user', items: [
            UserDropdownLink::make('notifications', 1)
                ->label(trans('froxlor-core::generic.notifications'))
                ->route(fn() => '#'),

            UserDropdownLink::make('help', 1)
                ->label(trans('froxlor-core::generic.documentation'))
                ->route(fn() => '#'),
        ]);

        UI::push('user', items: [
            UserDropdownLink::make('logout', 2)
                ->label(trans('froxlor-core::generic.logout'))
                ->route(fn() => route('logout')),
        ]);
    }
}
