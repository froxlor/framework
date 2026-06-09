<?php

namespace Froxlor\Mail\Listeners;

use Froxlor\Core\Events\Api\ResourceResponseMade;

class ExtendResourceResponse
{
    public function handle(ResourceResponseMade $event): void
    {
        if ($event->resource->resource instanceof \Froxlor\Domain\Models\Domain) {
            /**
             * add `has_mails` boolean property to each collection entry if it's a Domain model
             */
            $domain = clone $event->resource->resource;
            $event->resource->resource->has_mails = $domain->mail_addresses()->exists();
            if ($event->resource->resource->has_mails) {
                $event->resource->resource->count_mails = $domain->mail_addresses()->count();
            }
        }
    }
}
