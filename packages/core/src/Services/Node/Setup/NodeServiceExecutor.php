<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Models\Node;

/** Execution boundary, replaceable by a restricted node helper in a later deployment. */
interface NodeServiceExecutor
{
    /** Apply only a current, server-generated plan after node authorization. */
    public function apply(Node $node, NodeSetupPlan $plan): NodeSetupResult;
}
