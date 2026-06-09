<?php

namespace Froxlor\Mail\Providers;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Mail\Listeners;
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
        ],
        CoreEvents\Api\ResourceResponseMade::class => [
            Listeners\ExtendResourceResponse::class,
        ],
        CoreEvents\Api\CollectionResponseMade::class => [
            Listeners\ExtendCollectionResponse::class,
        ],
    ];
}
