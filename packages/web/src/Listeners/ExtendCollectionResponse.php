<?php

namespace Froxlor\Web\Listeners;

use Froxlor\Core\Events\Api\CollectionResponseMade;

class ExtendCollectionResponse
{
    public function handle(CollectionResponseMade $event): void
    {
        $event->collection->collection->map(function ($resource) {
            if ($resource->resource::class === \Froxlor\Domain\Models\Domain::class) {
                /**
                 * add `has_vhost` boolean property to each collection entry if it's a Domain model
                 */
                $domain = clone $resource->resource;
                $resource->resource->has_vhost = $domain->domain_vhost()->exists();
            } elseif ($resource->resource::class === \Froxlor\Core\Models\Node::class) {
                // nothing yet
            }
        });
    }
}
