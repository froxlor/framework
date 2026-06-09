<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\NodeEnvironment;

class NodeEnvironmentObserver
{
    /**
     * Handle the Node-Environment "created" event.
     */
    public function created(NodeEnvironment $nodeEnvironment): void
    {
        // increment last_username_number and last_guid_number
        $nodeEnvironment->node->setSetting('node.last_username_number', ($nodeEnvironment->node->getSetting('node.last_username_number') + 1));
        $nodeEnvironment->node->setSetting('node.last_guid_number', $nodeEnvironment->node->latestGuid + 1);
    }
}
