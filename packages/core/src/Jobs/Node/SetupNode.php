<?php

namespace Froxlor\Core\Jobs\Node;

use Froxlor\Core\Services\Node\Setup\NodeSetupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Queue payload contains IDs only; user, permissions and plan are resolved afresh. */
class SetupNode implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1260;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $nodeId, public readonly string $requestId)
    {
        $this->onConnection('node-setup');
        $this->onQueue('node-setup');
        $this->afterCommit();
    }

    public function handle(NodeSetupService $setups): void
    {
        $setups->execute($this->nodeId, $this->requestId);
    }

    public function failed(?Throwable $exception): void
    {
        app(NodeSetupService::class)->fail($this->nodeId, $this->requestId,
            'Node setup job failed or timed out. Inspect the node journal before retrying.');
    }
}
