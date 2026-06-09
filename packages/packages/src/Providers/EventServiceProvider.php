<?php

namespace Froxlor\Packages\Providers;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Packages\Listeners;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the package.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CoreEvents\DatabaseSeeded::class => [
            Listeners\SeedDatabase::class,
        ]
    ];
}
