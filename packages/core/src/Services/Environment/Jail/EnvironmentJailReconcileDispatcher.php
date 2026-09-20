<?php

namespace Froxlor\Core\Services\Environment\Jail;

use Froxlor\Core\Jobs\Environment\SyncEnvironmentJail;
use Froxlor\Core\Models\Environment;

/** Central fan-out for package lifecycle/configuration changes. */
final class EnvironmentJailReconcileDispatcher
{
    public function dispatchForPackage(string $package): int
    {
        unset($package);
        $count = 0;
        Environment::query()->with('nodes')->chunkById(100, function ($environments) use (&$count): void {
            foreach ($environments as $environment) {
                foreach ($environment->nodes as $node) {
                    SyncEnvironmentJail::dispatch($environment, $node)->afterCommit();
                    $count++;
                }
            }
        });
        return $count;
    }

    public function dispatchForEnvironment(Environment $environment): int
    {
        $count = 0;
        foreach ($environment->nodes as $node) {
            SyncEnvironmentJail::dispatch($environment, $node)->afterCommit();
            $count++;
        }
        return $count;
    }
}
