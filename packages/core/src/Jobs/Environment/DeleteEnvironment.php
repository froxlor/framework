<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Models\Environment;
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
    public function __construct(private readonly Environment $environment)
    {
    }

    /**
     * Remove the environment jail from every assigned node.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->environment->loadMissing(['nodes', 'tenant']);

        foreach ($this->environment->nodes as $node) {
            Cache::lock("nodes:{$node->id}:environment-delete", 120)->block(30, function () use ($node) {
                $node = $node->refresh();
                $adapter = $node->adapter();

                if (!$adapter->isConnected()) {
                    throw new NodeException(trans('Unable to connect to node ":node"...', ['node' => $node->hostname]));
                }

                $nodeBaseDir = $node->getSetting('node.basedir', '/var/environments');
                $envBaseDir = $nodeBaseDir . '/' . $this->environment->id;
                $unixName = $node->pivot->unix_name;
                $guid = $node->pivot->guid;

                if ($adapter->exec($this->deleteCommands($envBaseDir, $unixName)) === false) {
                    throw new NodeException(trans('Unable to delete environment-directory ":dir".', ['dir' => $envBaseDir]));
                }

                $this->environment->nodes()->detach($node->id);

                Audit::notice('environment "' . $this->environment->name . '" deleted from node "' . $node->name . '"', $this->environment->tenant, $this->environment, [
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
     * Mounts are lazily unmounted before deleting files so still-open handles
     * from previous sessions do not leave the jail directory behind.
     *
     * @return array<int, string>
     */
    private function deleteCommands(string $envBaseDir, string $unixName): array
    {
        return [
            'JAILBASE=' . escapeshellarg(rtrim($envBaseDir, '/')),
            'JAILUSER=' . escapeshellarg($unixName),
            'if mountpoint -q "$JAILBASE/dev/pts"; then umount -l "$JAILBASE/dev/pts"; fi',
            'if mountpoint -q "$JAILBASE/proc"; then umount -l "$JAILBASE/proc"; fi',
            'if getent passwd "$JAILUSER" >/dev/null; then pkill -u "$JAILUSER" || true; fi',
            'if getent passwd "$JAILUSER" >/dev/null; then userdel "$JAILUSER"; fi',
            'if getent group "$JAILUSER" >/dev/null; then groupdel "$JAILUSER"; fi',
            'if [ -n "$JAILBASE" ] && [ "$JAILBASE" != "/" ] && [ -d "$JAILBASE" ]; then rm -rf -- "$JAILBASE"; fi',
        ];
    }
}
