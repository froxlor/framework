<?php

namespace Froxlor\Core\Events\Node;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NodeExplored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Node $node)
    {
        //
        Audit::log('node "' . $node->name . '" explored', $node->tenant, null, [
            'node_id' => $node->id,
        ]);
    }
}
