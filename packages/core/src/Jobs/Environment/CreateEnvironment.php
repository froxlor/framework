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
        Cache::lock("nodes:{$this->node->id}:environment-create", 120)->block(30, function () {
            $node = $this->node->refresh();
            $environment = $this->environment->refresh();

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

            if (!$adapter->storagePut('/tmp/createhome.sh', $createJailCommand)) {
                throw new NodeException(trans('Unable to upload jail creation script.'));
            }

            if ($adapter->exec([
                'apt install sudo jailkit -y',
                'chmod +x /tmp/createhome.sh',
                '/tmp/createhome.sh',
                'rm -f /tmp/createhome.sh'
            ]) === false) {
                $adapter->storageDelete('/tmp/createhome.sh');

                throw new NodeException(trans('Unable to create jail.'));
            }

            // connect environment with node (must be mode=main)
            $environment->nodes()->attach($node, [
                'unix_name' => $unixName,
                'guid' => $guid,
                'mode' => 'main'
            ]);

            event(new EnvironmentCreated($environment));
            Audit::notice('environment "' . $environment->name . '" created on node "' . $node->name . '"', $environment->tenant, $environment, [
                'node_id' => $node->id,
                'unix_name' => $unixName,
                'guid' => $guid,
            ]);
        });
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
