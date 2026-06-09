<?php

namespace Froxlor\Web\Listeners;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Support\Setting;
use Froxlor\Domain\Models\Domain;
use Froxlor\Web\Http\Requests\StoreDomainVhostRequest;

class ExtendResourceValidation
{
    public function handle(CoreEvents\Api\ResourceValidating $event): void
    {
        if ($event->class === Domain::class) {
            if (!Setting::get('web.enabled')) {
                return;
            }
            // attach to all store-events
            if (str_ends_with(strtolower($event->action), 'store')) {
                $event->rules = array_merge($event->rules, (new StoreDomainVhostRequest())->rules());
            }
        }
    }
}
