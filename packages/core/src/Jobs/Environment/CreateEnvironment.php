<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Events\Tenant\EnvironmentCreated;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Exceptions\NodeException;
use Froxlor\Core\Support\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateEnvironment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Environment $environment, private readonly Node $node)
    {
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(): void
    {
        Cache::lock("nodes:{$this->node->id}:environment-create", 600)->block(60, function () {
            $node = $this->node->refresh();
            $environment = $this->environment->refresh();

            // Queue retries must not provision an already attached environment again.
            if ($environment->nodes()->whereKey($node->id)->exists()) {
                return;
            }

            // base-directory for node...
            $nodeBaseDir = $node->getSetting('node.basedir', '/var/environments');
            $adapter = $node->adapter();

            if (!$adapter->isConnected()) {
                throw new NodeException(trans('Unable to connect to node ":node"...', ['node' => $node->hostname]));
            }

            $unixName = $node->latestUnixName;
            $guid = $this->resolveNextFreeGuid($adapter, $node->nextGuid);

            if (!$adapter->storageExists($nodeBaseDir)) {
                Log::notice(trans('Data base-directory ":dir" does not exists. Creating...', ['dir' => $nodeBaseDir]));
                if ($adapter->exec([
                    'mkdir -p ' . escapeshellarg($nodeBaseDir)
                ]) === false) {
                    throw new NodeException(trans('Unable to create node base-directory ":dir".', ['dir' => $nodeBaseDir]));
                }
            }

            // base-directory for environment...
            $envBaseDir = $nodeBaseDir . '/' . $environment->id;
            if ($adapter->storageExists($envBaseDir)) {
                throw new NodeException(trans('Data environment-directory ":dir" already exists.', ['dir' => $envBaseDir]));
            }

            $createJailCommand = view('froxlor-core::node.scripts.create_jail', [
                'userRootDir' => rtrim($envBaseDir, '/'),
                'userHomeDir' => $envBaseDir . '/home',
                'userName' => $unixName,
                'userGuid' => $guid,
            ])->render();

            $scriptPath = '/tmp/createhome-' . $environment->id . '.sh';

            if (!$adapter->storagePut($scriptPath, $createJailCommand)) {
                throw new NodeException(trans('Unable to upload jail creation script.'));
            }

            try {
                if ($adapter->exec([
                    'if ! command -v jk_init >/dev/null 2>&1 || ! command -v jk_jailuser >/dev/null 2>&1; then',
                    'export DEBIAN_FRONTEND=noninteractive',
                    'apt-get update',
                    'apt-get install -y sudo jailkit',
                    'fi',
                    'chmod +x ' . escapeshellarg($scriptPath),
                    escapeshellarg($scriptPath),
                    'rm -f ' . escapeshellarg($scriptPath),
                ]) === false) {
                    throw new NodeException(trans('Unable to create jail.'));
                }

                // connect environment with node (must be mode=main)
                $environment->nodes()->attach($node, [
                    'unix_name' => $unixName,
                    'guid' => $guid,
                    'mode' => 'main'
                ]);
            } catch (Throwable $exception) {
                $this->cleanupFailedProvisioning($adapter, $envBaseDir, $unixName, $scriptPath, $node, $environment);

                throw $exception;
            } finally {
                // The script is removed by the successful command chain as well; this also
                // covers failures before that final command can run.
                try {
                    $adapter->storageDelete($scriptPath);
                } catch (Throwable $cleanupException) {
                    Log::warning('Unable to remove temporary environment creation script.', [
                        'node_id' => $node->id,
                        'environment_id' => $environment->id,
                        'script' => $scriptPath,
                        'exception' => $cleanupException,
                    ]);
                }
            }

            event(new EnvironmentCreated($environment));
            Audit::notice('environment "' . $environment->name . '" created on node "' . $node->name . '"', $environment->tenant, $environment, [
                'node_id' => $node->id,
                'unix_name' => $unixName,
                'guid' => $guid,
            ]);
        });
    }

    /**
     * Remove remote state left behind by a failed jail creation or database attach.
     */
    private function cleanupFailedProvisioning(
        Adapter $adapter,
        string $envBaseDir,
        string $unixName,
        string $scriptPath,
        Node $node,
        Environment $environment,
    ): void {
        try {
            $cleanupResult = $adapter->exec([
                'JAILBASE=' . escapeshellarg(rtrim($envBaseDir, '/')),
                'JAILUSER=' . escapeshellarg($unixName),
                'if mountpoint -q "$JAILBASE/dev/pts"; then umount -l "$JAILBASE/dev/pts" || true; fi',
                'if mountpoint -q "$JAILBASE/proc"; then umount -l "$JAILBASE/proc" || true; fi',
                'if getent passwd "$JAILUSER" >/dev/null; then pkill -u "$JAILUSER" || true; fi',
                'if getent passwd "$JAILUSER" >/dev/null; then userdel "$JAILUSER" || true; fi',
                'if getent group "$JAILUSER" >/dev/null; then groupdel "$JAILUSER" || true; fi',
                'if [ -n "$JAILBASE" ] && [ "$JAILBASE" != "/" ] && [ -d "$JAILBASE" ]; then rm -rf -- "$JAILBASE"; fi',
            ]);

            if ($cleanupResult === false) {
                Log::warning('Unable to clean up failed environment provisioning.', [
                    'node_id' => $node->id,
                    'environment_id' => $environment->id,
                    'script' => $scriptPath,
                ]);
            }
        } catch (Throwable $cleanupException) {
            Log::warning('Unable to clean up failed environment provisioning.', [
                'node_id' => $node->id,
                'environment_id' => $environment->id,
                'script' => $scriptPath,
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
            'candidate=' . escapeshellarg((string)$guid),
            'while getent passwd "$candidate" >/dev/null || getent group "$candidate" >/dev/null; do',
            'candidate=$((candidate + 1))',
            'done',
            'printf "%s" "$candidate"',
        ]);

        if ($resolvedGuid === false || !ctype_digit(trim($resolvedGuid))) {
            throw new NodeException(trans('Unable to resolve next free guid.'));
        }

        return (int)trim($resolvedGuid);
    }
}
