<?php

namespace Froxlor\Web\Listeners;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Domain\Models\Domain;
use Froxlor\Web\Services\DomainVhostService;

class CreateDomain
{
    public function handle(CoreEvents\Api\ResourceCreated $event): void
    {
        if ($event->model instanceof Domain) {
            if ($event->validatedData['is_http_domain']) {
                unset($event->validatedData['is_http_domain']);
                $event->model->update(['properties->web->enabled' => true]);
                app(DomainVhostService::class)->createDomainVhostForNewDomain($event->model, $event->validatedData);
            }
        }
    }
}
