<?php

namespace Froxlor\Auth\Providers;

use Froxlor\Auth\Livewire\Actions\Logout;
use Froxlor\UI\Pushable\UserDropdownLink;
use Froxlor\UI\Support\UI;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FroxlorAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-auth');

        // Language
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'froxlor-auth');

        // Livewire
        Livewire::component('froxlor-auth::logout', Logout::class);

        // User Interface
        $this->extendUserInterface();
    }

    public function register(): void
    {
        //
    }

    /**
     * Register navigation items.
     */
    private function extendUserInterface(): void
    {
        UI::push('user', items: [
            UserDropdownLink::make('logout', 9)
                ->label(trans('froxlor-auth::generic.logout'))
                ->route(fn() => route('logout')),
        ]);
    }
}
