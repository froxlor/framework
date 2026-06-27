<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantUsage;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\Resource;
use RuntimeException;

class NodeObserver
{
    /**
     * Handle the Node "creating" event.
     *
     * @throws ResourceLimitException|UnknownTenantUserException|InvalidResourceException
     */
    public function creating(Node $node): void
    {
        if (empty($node->tenant_id) || !auth()->check()) {
            return;
        }

        $targetTenant = Tenant::query()->findOrFail($node->tenant_id);
        $actingTenant = Resource::actingTenantFor(auth()->user(), $targetTenant);

        if ($actingTenant === null
            || !Resource::hasUsageAvailable($actingTenant, Node::class, auth()->user())
            || (!$actingTenant->is($targetTenant) && !Resource::hasUsageAvailable($targetTenant, Node::class, auth()->user()))) {
            throw new ResourceLimitException('Resource limit exceeded (' . Node::getResourceKey() . ')');
        }
    }

    /**
     * Handle the Node "created" event.
     * @throws \Exception
     */
    public function created(Node $node): void
    {
        // add node-specific setting-templates for this node
        $node->addSetting('node.last_username_number', 0, null, 'integer', ['visible' => false]);
        $node->addSetting('node.last_guid_number', 9999, null, 'integer', ['visible' => false]);

        if (!empty($node->tenant_id) && auth()->check()) {
            $actingTenant = Resource::actingTenantFor(auth()->user(), $node->tenant);
            if ($actingTenant !== null) {
                Resource::addUsage($actingTenant, $node, auth()->user());
                if (!$actingTenant->is($node->tenant)) {
                    Resource::addUsage($node->tenant, $node, auth()->user());
                }
            }
        }

        Audit::info('node "' . $node->name . '" created', $node->tenant, null, [
            'node_id' => $node->id,
        ]);
    }

    /**
     * Handle the Node "updated" event.
     */
    public function updated(Node $node): void
    {
        Audit::info('node "' . $node->name . '" updated', $node->tenant, null, [
            'node_id' => $node->id,
        ]);
    }

    /**
     * Handle the Node "deleting" event.
     */
    public function deleting(Node $node): void
    {
        if ($node->environments()->exists()) {
            throw new RuntimeException('Cannot delete node while environments are assigned.');
        }
    }

    /**
     * Handle the Node "deleted" event.
     */
    public function deleted(Node $node): void
    {
        if (!empty($node->tenant_id)) {
            TenantUsage::query()
                ->where('resource_key', Node::getResourceKey())
                ->where('resource_id', $node->id)
                ->delete();
        }

        Audit::info('node "' . $node->name . '" deleted', $node->tenant, null, [
            'node_id' => $node->id,
        ]);
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
