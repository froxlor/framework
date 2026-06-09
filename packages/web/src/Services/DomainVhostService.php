<?php

namespace Froxlor\Web\Services;

use Froxlor\Core\Support\Setting;
use Froxlor\Domain\Models\Domain;
use Froxlor\Web\Models\DomainSslVhost;
use Froxlor\Web\Models\DomainVhost;

class DomainVhostService
{
    public function createDomainVhostForNewDomain(Domain $domain, array $data): void
    {
        if (!Setting::get('web.enabled') && $domain->properties['web']['enabled']) {
            // nothing to do for us
            return;
        }
        // vhosts only make sense if domain is assigned to an environment (and thus to a node)
        if ($domain->node()->exists()) {
            // default Vhost
            $http_data = $data['vhost'];
            if (!empty($http_data)) {
                $http_data['domain_id'] = $domain->id;
                $http_data['node_id'] = $domain->node->id;
                $domainVhost = DomainVhost::query()->create($http_data);

                // check for ssl vhost
                if (Setting::get('web.ssl_enabled')) {
                    // ssl vhost
                    $https_data = $data['ss_vhost'] ?? [];
                    if (!empty($https_data)) {
                        $https_data['domain_vhost_id'] = $domainVhost->id;
                        DomainSslVhost::query()->create($https_data);
                    }
                }
            }
        }
    }
}
