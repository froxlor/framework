<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Events\Tenant\EnvironmentCreated;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        $unixName = $this->node->latestUnixName;
        $guid = $this->node->latestGuid;

        // connect environment with node (must be mode=main)
        $this->environment->nodes()->attach($this->node, [
            'unix_name' => $unixName,
            'guid' => $guid,
            'mode' => 'main'
        ]);

        // base-directory for node...
        $nodeBaseDir = $this->node->getSetting('node.basedir', '/var/environments');
        $adapter = $this->node->adapter();

        if (!$adapter->isConnected()) {
            Log::warning(trans('Unable to connect to node ":node"...', ['node' => $this->node->hostname]));
            return;
        }
        if (!$adapter->storageExists($nodeBaseDir)) {
            Log::notice(trans('Data base-directory ":dir" does not exists. Creating...', ['dir' => $nodeBaseDir]));
            $adapter->exec([
                'mkdir -p ' . $nodeBaseDir
            ]);
        }

        // base-directory for environment...
        $envBaseDir = $nodeBaseDir . '/' . $this->environment->id;
        if ($adapter->storageExists($envBaseDir)) {
            Log::notice(trans('Data environment-directory ":dir" already exists. Aborting...', ['dir' => $envBaseDir]));
            return;
        }

        $createJailCommand = view('froxlor-core::node.scripts.create_jail', [
            'userRootDir' => rtrim($envBaseDir, '/'),
            'userHomeDir' => $envBaseDir . '/home',
            'userName' => $unixName,
            'userGuid' => $guid,
        ])->render();

        $adapter->storagePut('/tmp/createhome.sh', $createJailCommand);

        if ($adapter->exec([
            'apt install sudo jailkit -y',
            'chmod +x /tmp/createhome.sh',
            '/tmp/createhome.sh',
            'rm -f /tmp/createhome.sh'
        ]) === false) {
            Log::error(trans('Unable to create jail.'));
            return;
        }

        event(new EnvironmentCreated($this->environment));
    }
}
