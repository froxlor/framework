<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Services\Environment\Jail\JailContext;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Froxlor\Core\Support\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DeleteEnvironment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Environment $environment) {}

    /**
     * Remove the environment jail from every assigned node.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->environment->loadMissing(['nodes', 'tenant']);

        foreach ($this->environment->nodes as $node) {
            Cache::lock("nodes:{$node->id}:environments", 2040)->block(30, function () use ($node) {
                $node = $this->environment->nodes()->whereKey($node->id)->first();
                if ($node === null) {
                    return;
                }
                $adapter = $node->adapter();

                if (! $adapter->isConnected()) {
                    throw new NodeException(trans('Unable to connect to node ":node"...', ['node' => $node->hostname]));
                }

                $unixName = $node->pivot->unix_name;
                $guid = $node->pivot->guid;
                $context = JailContext::forEnvironment($this->environment, $node, $unixName, (int) $guid, $node->pivot->jail_path);
                $envBaseDir = $context->root;

                if (trim((string) $adapter->exec($this->deleteCommands($context))) !== 'FROXLOR_JAIL_OK') {
                    throw new NodeException(trans('Unable to delete environment-directory ":dir".', ['dir' => $envBaseDir]));
                }

                $this->environment->nodes()->detach($node->id);

                Audit::notice('environment "'.$this->environment->name.'" deleted from node "'.$node->name.'"', $this->environment->tenant, $this->environment, [
                    'node_id' => $node->id,
                    'unix_name' => $unixName,
                    'guid' => $guid,
                ]);
            });
        }
    }

    /**
     * Build the shell commands that remove a jail and its system account.
     *
     * Reject identity/path mismatches and unexpected mounts before destructive changes.
     *
     * @return array<int, string>
     */
    private function deleteCommands(JailContext $context): array
    {
        $payload = base64_encode(json_encode(['operation' => 'delete', 'root' => $context->root,
            'user' => $context->user, 'guid' => $context->guid], JSON_THROW_ON_ERROR));
        $helper = file_get_contents(__DIR__.'/../../../resources/node/reconcile_jail.py');

        return ['printf %s '.escapeshellarg(base64_encode($helper)).' | base64 -d | timeout --signal=TERM --kill-after=30s 120s /usr/bin/python3 - '.escapeshellarg($payload)];
    }
}
