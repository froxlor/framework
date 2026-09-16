<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Audit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Explicit compatibility executor using the existing privileged node adapter.
 * This is not a restricted root helper: installed provider packages are trusted code.
 */
final readonly class AdapterNodeServiceExecutor implements NodeServiceExecutor
{
    public function __construct(private NodeServicePlanner $planner, private NodeSetupScript $scripts) {}

    public function apply(Node $node, NodeSetupPlan $plan): NodeSetupResult
    {
        Gate::authorize('update', $node);
        if (! $node->exists || $plan->nodeId !== (string) $node->getKey()) {
            throw new LogicException('Setup plan belongs to another or unpersisted node.');
        }
        $node->refresh();
        Gate::authorize('update', $node);

        $selection = [];
        foreach ($plan->services as $key => $service) {
            $selection[$service['role']] = $key;
        }
        $current = $this->planner->plan($node, $selection);
        if (! hash_equals($current->fingerprint(), $plan->fingerprint())) {
            throw new LogicException('Node setup plan is stale. Generate a new plan.');
        }

        $runId = $node->setup_status === 'running' && $node->setup_request_id
            ? $node->setup_request_id : (string) Str::ulid();
        $context = ['node_id' => $node->id, 'run_id' => $runId, 'fingerprint' => $current->fingerprint()];
        Audit::notice('node service setup started', $node->tenant, context: $context);

        try {
            $script = $this->scripts->compile($current, $runId);
            // Only base64 enters the adapter heredoc. No rendered value can terminate it.
            $command = 'printf %s '.escapeshellarg(base64_encode($script))
                .' | base64 -d | /usr/bin/timeout --signal=TERM --kill-after=30s 1200s /bin/bash -se';
            $output = $node->adapter()->exec([$command]);
            // Some adapters do not reliably propagate exit codes; require the final marker too.
            if (! is_string($output) || trim($output) !== 'FROXLOR_SETUP_OK:'.$runId) {
                throw new RuntimeException('Node setup failed; inspect the root-owned node setup journal.');
            }
        } catch (Throwable) {
            Audit::error('node service setup failed', $node->tenant, context: $context);
            // Adapter exceptions or stderr can contain secrets. Do not propagate them.
            throw new RuntimeException('Node setup failed; inspect the root-owned node setup journal.');
        }

        Audit::notice('node service setup completed', $node->tenant, context: $context);

        return new NodeSetupResult($runId, $current->fingerprint());
    }
}
