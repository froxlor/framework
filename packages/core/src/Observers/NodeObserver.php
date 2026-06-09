<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\Node;

class NodeObserver
{
    /**
     * Handle the Node "created" event.
     * @throws \Exception
     */
    public function created(Node $node): void
    {
        // add node-specific setting-templates for this node
        $node->addSetting('node.last_username_number', 0, null, 'integer', ['visible' => false]);
        $node->addSetting('node.last_guid_number', 9999, null, 'integer', ['visible' => false]);
    }

    /**
     * Handle the Node "updated" event.
     */
    public function updated(Node $node): void
    {
        //
    }

    /**
     * Handle the Node "deleted" event.
     */
    public function deleted(Node $node): void
    {
        //
    }

    /**
     * Handle the Node "restored" event.
     */
    public function restored(Node $node): void
    {
        //
    }

    /**
     * Handle the Node "force deleted" event.
     */
    public function forceDeleted(Node $node): void
    {
        //
    }
}
