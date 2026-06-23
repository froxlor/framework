<?php

namespace Froxlor\Core\Providers;

use Froxlor\Core\Events;
use Froxlor\Core\Listeners;
use Froxlor\Core\Support\Audit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

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

    public function boot(): void
    {
        // audit log eloquent events
        Event::listen('eloquent.*', function (string $eventName, array $data) {
            [$event, $type] = array_map('trim', explode(': ', $eventName));
            if (in_array($event, ['eloquent.updated', 'eloquent.created', 'eloquent.deleted', 'eloquent.restored'])) {
                Audit::info(
                    collect(Arr::dot([
                        'event' => $event,
                        'type' => $type,
                        'data' => $data[0]->getKey()
                    ]))
                        ->map(fn($v, $k) => "$k=$v")
                        ->implode(', ')
                );
            }
        });
    }
}
