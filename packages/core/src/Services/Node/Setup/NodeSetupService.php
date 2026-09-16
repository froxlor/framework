<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Jobs\Node\SetupNode;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Audit;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

/** Persisted scheduling and compare-and-swap state transitions for node setup. */
final readonly class NodeSetupService
{
    public function __construct(private NodeServicePlanner $planner, private NodeSetupActor $actors) {}

    /** Request/repeat the stored provider selection, initially the Core base provider only. */
    public function request(Node $node, User $actor, bool $initial = false): Node
    {
        return $this->actors->run($actor, function () use ($node, $actor, $initial): Node {
            return DB::transaction(function () use ($node, $actor, $initial): Node {
                $node = Node::query()->lockForUpdate()->findOrFail($node->id);
                Gate::forUser($actor)->authorize('update', $node);
                if ($initial && $node->setup_status !== null) {
                    return $node;
                }
                $since = $node->setup_status === 'running' ? $node->setup_started_at : $node->setup_requested_at;
                if (in_array($node->setup_status, ['pending', 'running'], true)
                    && $since !== null && $since->lt(now()->subMinutes(30))) {
                    // Explicit recovery only, beyond both worker timeout and broker retry window.
                    // The node-side journal/lock still blocks an unrecovered remote execution.
                    $node->setup_status = 'failed';
                    Audit::warning('expired node setup request replaced', $node->tenant,
                        context: ['node_id' => $node->id, 'request_id' => $node->setup_request_id]);
                }
                abort_if(in_array($node->setup_status, ['pending', 'running'], true), 409, 'Node setup is already pending or running.');

                $selection = $node->setup_selection ?: ['base-system' => 'froxlor/core:base-system'];
                try {
                    $plan = $this->planner->plan($node, $selection);
                } catch (InvalidArgumentException|LogicException) {
                    throw ValidationException::withMessages(['node' => 'Node setup cannot be planned. Check the explored platform, providers and settings.']);
                }
                $requestId = (string) Str::ulid();
                $node->forceFill([
                    'setup_status' => 'pending', 'setup_request_id' => $requestId,
                    'setup_requested_by' => $actor->id, 'setup_selection' => $selection,
                    'setup_fingerprint' => $plan->fingerprint(), 'setup_run_id' => null,
                    'setup_requested_at' => now(), 'setup_started_at' => null,
                    'setup_finished_at' => null, 'setup_error' => null,
                ])->saveQuietly();
                Audit::notice('node setup queued', $node->tenant, context: ['node_id' => $node->id, 'request_id' => $requestId]);

                DB::afterCommit(function () use ($node, $requestId): void {
                    try {
                        Bus::dispatch(new SetupNode($node->id, $requestId));
                    } catch (Throwable) {
                        $this->fail($node->id, $requestId, 'Node setup could not be queued.');
                        throw new RuntimeException('Node setup could not be queued.');
                    }
                });

                return $node;
            });
        });
    }

    /** Called exclusively by a successfully completed initial exploration. */
    public function afterInitialExploration(Node $node, ?string $actorId): void
    {
        if ($node->fresh()?->setup_status !== null) {
            return;
        }
        try {
            $actor = $actorId ? User::query()->find($actorId) : null;
            if ($actor === null) {
                throw new RuntimeException('Initial setup requires an initiating user.');
            }
            $this->request($node, $actor, initial: true);
        } catch (Throwable) {
            // Exploration itself succeeded. Expose setup scheduling failure separately.
            Node::query()->whereKey($node->id)->whereNull('setup_status')->update([
                'setup_status' => 'failed', 'setup_requested_by' => $actorId,
                'setup_requested_at' => now(), 'setup_finished_at' => now(),
                'setup_error' => 'Initial setup could not be scheduled. Check the initiating user, permissions and platform.',
            ]);
        }
    }

    public function execute(string $nodeId, string $requestId): void
    {
        // A duplicate delivery or an old retried job never starts a second installation.
        $claimed = Node::query()->whereKey($nodeId)->where('setup_request_id', $requestId)
            ->where('setup_status', 'pending')->update([
                'setup_status' => 'running', 'setup_started_at' => now(), 'setup_run_id' => $requestId,
            ]);
        if (! $claimed) {
            return;
        }

        try {
            $node = Node::query()->findOrFail($nodeId);
            $actor = User::query()->find($node->setup_requested_by);
            if ($actor === null) {
                throw new RuntimeException('Initiating user no longer exists.');
            }
            $this->actors->run($actor, function () use ($node, $actor, $requestId): void {
                Gate::forUser($actor)->authorize('update', $node);
                $plan = $this->planner->plan($node, $node->setup_selection);
                if (! hash_equals($node->setup_fingerprint, $plan->fingerprint())) {
                    throw new LogicException('Queued setup plan is stale.');
                }
                $result = app(NodeServiceExecutor::class)->apply($node, $plan);
                Node::query()->whereKey($node->id)->where('setup_request_id', $requestId)
                    ->where('setup_status', 'running')->update([
                        'setup_status' => 'succeeded', 'setup_run_id' => $result->runId,
                        'setup_finished_at' => now(), 'setup_error' => null,
                    ]);
            });
        } catch (Throwable) {
            $this->fail($nodeId, $requestId, 'Node setup failed. Check permissions, current configuration and the node setup journal.');
            // Never put raw adapter errors or rendered settings into failed_jobs.
            throw new RuntimeException('Node setup failed. See the node setup status and journal.');
        }
    }

    /** Safe for timeout hooks and old jobs; only the matching active request can fail. */
    public function fail(string $nodeId, string $requestId, string $message): void
    {
        $changed = Node::query()->whereKey($nodeId)->where('setup_request_id', $requestId)
            ->whereIn('setup_status', ['pending', 'running'])->update([
                'setup_status' => 'failed', 'setup_finished_at' => now(), 'setup_error' => $message,
            ]);
        if ($changed && ($node = Node::query()->find($nodeId))) {
            $actor = User::query()->find($node->setup_requested_by);
            if ($actor !== null) {
                $this->actors->run($actor, fn () => Audit::error('node setup request failed', $node->tenant,
                    context: ['node_id' => $nodeId, 'request_id' => $requestId]));
            }
        }
    }
}
