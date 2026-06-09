<?php

namespace Froxlor\Web\Listeners;

use Froxlor\Core\Events\Api\ResourceResponseMade;

class ExtendResourceResponse
{
    public function handle(ResourceResponseMade $event): void
    {
        if ($event->resource->resource instanceof \Froxlor\Domain\Models\Domain) {
            /**
             * add `domain_vhost` DomainVhost property to the Domain model
             */
            $domain = $event->resource->resource;
            $domain->load('domain_vhost', 'domain_vhost.domainSslVhost');
        } else if ($event->resource->resource instanceof \Froxlor\Core\Models\Node) {
            /**
             * add `domain_vhost` DomainVhost property to Node model
             */
            // don't append all loaded relations, hence 'clone'
            $node = clone $event->resource->resource;
            $node->nodeInterfaces->load('domain_vhosts');
        }
    }
}
