<?php

namespace Froxlor\Core\Providers;

use Froxlor\Core\Events;
use Froxlor\Core\Listeners;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the package.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Events\DatabaseSeeding::class => [
            Listeners\SeedDatabase::class,
        ]
    ];

}
