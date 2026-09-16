<?php

namespace Froxlor\Core\Jobs\Environment;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Environment\Jail\JailReconciler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SyncEnvironmentJail implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1860;

    public function __construct(private readonly Environment $environment, private readonly Node $node)
    {
        $this->onConnection('environment-jails')->onQueue('environment-jails')->afterCommit();
    }

    public function handle(JailReconciler $reconciler): void
    {
        Cache::lock("nodes:{$this->node->id}:environments", 2040)->block(30, function () use ($reconciler): void {
            $environment = $this->environment->fresh();
            $node = $this->node->fresh();
            // Deletion before a queued sync is a harmless no-op, not recreation.
            if ($environment !== null && $node !== null && $environment->nodes()->whereKey($node->id)->exists()) {
                $reconciler->sync($environment, $node);
            }
        });
    }
}
