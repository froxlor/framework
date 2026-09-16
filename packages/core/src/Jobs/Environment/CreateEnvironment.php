<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Events\Tenant\EnvironmentCreated;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Environment\Jail\JailContext;
use Froxlor\Core\Services\Environment\Jail\JailReconciler;
use Froxlor\Core\Services\Environment\Jail\JailRegistry;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Froxlor\Core\Support\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CreateEnvironment implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1860;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Environment $environment, private readonly Node $node)
    {
        $this->onConnection('environment-jails')->onQueue('environment-jails')->afterCommit();
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        Cache::lock("nodes:{$this->node->id}:environments", 2040)->block(60, function () {
            $node = $this->node->refresh();
            $environment = $this->environment->refresh();

            // Queue retries must not provision an already attached environment again.
            if (($attached = $environment->nodes()->whereKey($node->id)->first()) !== null) {
                app(JailReconciler::class)->sync($environment, $node);
                if ($attached->pivot->jail_manifest === null) {
                    $this->recordCreated($environment, $node, $attached->pivot->unix_name, (int) $attached->pivot->guid);
                }

                return;
            }

            $adapter = $node->adapter();

            if (! $adapter->isConnected()) {
                throw new NodeException(trans('Unable to connect to node ":node"...', ['node' => $node->hostname]));
            }

            $unixName = $node->latestUnixName;
            $guid = $this->resolveNextFreeGuid($adapter, $node->nextGuid);
            $context = JailContext::forEnvironment($environment, $node, $unixName, $guid);
            // Provider conflicts and unsafe paths must fail before creating anything.
            app(JailRegistry::class)->plan($context);

            // base-directory for environment...
            $envBaseDir = $context->root;
            if ($adapter->storageExists($envBaseDir)) {
                throw new NodeException(trans('Data environment-directory ":dir" already exists.', ['dir' => $envBaseDir]));
            }

            $token = (string) Str::uuid();
            $createJailCommand = view('froxlor-core::node.scripts.create_jail', [
                'userRootDir' => rtrim($envBaseDir, '/'),
                'userHomeDir' => $envBaseDir.'/home',
                'userName' => $unixName,
                'userGuid' => $guid,
                'creationToken' => $token,
            ])->render();

            try {
                if ($adapter->exec([
                    'if ! command -v jk_init >/dev/null 2>&1 || ! command -v jk_jailuser >/dev/null 2>&1 || ! command -v python3 >/dev/null 2>&1; then',
                    'timeout 180s /bin/bash -ec '.escapeshellarg('export DEBIAN_FRONTEND=noninteractive; apt-get update; apt-get install -y sudo jailkit python3').' >&2',
                    'fi',
                    'printf %s '.escapeshellarg(base64_encode($createJailCommand)).' | base64 -d | /usr/bin/timeout --signal=TERM --kill-after=30s 300s /bin/bash -se',
                ]) !== 'FROXLOR_JAIL_CREATED') {
                    throw new NodeException(trans('Unable to create jail.'));
                }

                // connect environment with node (must be mode=main)
                DB::transaction(fn () => $environment->nodes()->attach($node, [
                    'unix_name' => $unixName,
                    'guid' => $guid,
                    'jail_path' => $envBaseDir,
                    'mode' => 'main',
                ]));
            } catch (Throwable $exception) {
                $this->cleanupFailedProvisioning($adapter, $envBaseDir, $unixName, $guid, $token, $node, $environment);

                throw $exception;
            }

            // Extension failure must NOT destroy an attached jail. Retry only reconciles it.
            app(JailReconciler::class)->sync($environment, $node);

            $this->recordCreated($environment, $node, $unixName, $guid);
        });
    }

    private function recordCreated(Environment $environment, Node $node, string $unixName, int $guid): void
    {
        event(new EnvironmentCreated($environment));
        Audit::notice('environment "'.$environment->name.'" created on node "'.$node->name.'"', $environment->tenant, $environment, [
            'node_id' => $node->id, 'unix_name' => $unixName, 'guid' => $guid,
        ]);
    }

    /**
     * Remove remote state left behind by a failed jail creation or database attach.
     */
    private function cleanupFailedProvisioning(
        Adapter $adapter,
        string $envBaseDir,
        string $unixName,
        int $guid,
        string $token,
        Node $node,
        Environment $environment,
    ): void {
        try {
            $payload = base64_encode(json_encode(['operation' => 'delete', 'root' => $envBaseDir,
                'user' => $unixName, 'guid' => $guid, 'cleanup_token' => $token], JSON_THROW_ON_ERROR));
            $helper = file_get_contents(__DIR__.'/../../../resources/node/reconcile_jail.py');
            $cleanupResult = $adapter->exec(['printf %s '.escapeshellarg(base64_encode($helper))
                .' | base64 -d | timeout --signal=TERM --kill-after=30s 120s /usr/bin/python3 - '.escapeshellarg($payload)]);

            if (trim((string) $cleanupResult) !== 'FROXLOR_JAIL_OK') {
                Log::warning('Unable to clean up failed environment provisioning.', [
                    'node_id' => $node->id,
                    'environment_id' => $environment->id,
                ]);
            }
        } catch (Throwable $cleanupException) {
            Log::warning('Unable to clean up failed environment provisioning.', [
                'node_id' => $node->id,
                'environment_id' => $environment->id,
                'exception' => $cleanupException,
            ]);
        }
    }

    /**
     * Return the first UID/GID not already known to the target node.
     *
     * @throws NodeException
     */
    private function resolveNextFreeGuid(Adapter $adapter, int $guid): int
    {
        $resolvedGuid = $adapter->exec([
            'candidate='.escapeshellarg((string) $guid),
            'while getent passwd "$candidate" >/dev/null || getent group "$candidate" >/dev/null; do',
            'candidate=$((candidate + 1))',
            'done',
            'printf "%s" "$candidate"',
        ]);

        if ($resolvedGuid === false || ! ctype_digit(trim($resolvedGuid))) {
            throw new NodeException(trans('Unable to resolve next free guid.'));
        }

        return (int) trim($resolvedGuid);
    }
}
