<?php

namespace Froxlor\Mail\Listeners;

use Froxlor\Core\Events\Api\CollectionResponseMade;

class ExtendCollectionResponse
{
    public function handle(CollectionResponseMade $event): void
    {
        $event->collection->collection->map(function ($resource) {
            if ($resource->resource::class === \Froxlor\Domain\Models\Domain::class) {
                /**
                 * add `has_mails` boolean property to each collection entry if it's a Domain model
                 */
                $domain = clone $resource->resource;
                $resource->resource->has_mails = $domain->mail_addresses()->exists();
                if ($resource->resource->has_mails) {
                    $resource->resource->count_mails = $domain->mail_addresses()->count();
                }
            }
        });
    }
}
