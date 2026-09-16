<?php

namespace Froxlor\Core\Services\Environment\Jail;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Froxlor\Core\Support\Audit;
use Illuminate\Support\Facades\DB;

/** Internal provisioning service. Public entry points must authorize before dispatching. */
final readonly class JailReconciler
{
    public function __construct(private JailRegistry $registry) {}

    /** Caller holds the shared node environment lifecycle lock. */
    public function sync(Environment $environment, Node $node): void
    {
        $attachment = $environment->nodes()->whereKey($node->id)->firstOrFail()->pivot;
        $context = JailContext::forEnvironment($environment, $node, $attachment->unix_name, (int) $attachment->guid, $attachment->jail_path);
        $plan = $this->registry->plan($context);
        $payload = ['root' => $context->root, 'user' => $context->user, 'guid' => $context->guid, 'plan' => $plan];
        $encoded = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
        $helper = file_get_contents(__DIR__.'/../../../../resources/node/reconcile_jail.py');
        $command = 'printf %s '.escapeshellarg(base64_encode($helper))
            .' | base64 -d | /usr/bin/timeout --signal=TERM --kill-after=30s 1200s /usr/bin/python3 - '.escapeshellarg($encoded);
        // Marker validates success even when an adapter swallows remote exit codes.
        if (trim((string) $node->adapter()->exec([$command])) !== 'FROXLOR_JAIL_OK') {
            throw new NodeException('Jail reconciliation failed. Inspect the node; managed state is retained for retry.');
        }
        DB::table('node_environments')->where('node_id', $node->id)->where('environment_id', $environment->id)
            ->update(['jail_path' => $context->root, 'jail_manifest' => json_encode($plan, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        Audit::info('environment jail reconciled', $environment->tenant, $environment, [
            'node_id' => $node->id, 'providers' => array_keys($plan['providers']),
        ]);
    }
}
