<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Node\Setup\NodeSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Explicit setup requests and persisted status; no client-supplied execution plans. */
class NodeSetupController extends Controller
{
    public function show(Node $node): JsonResponse
    {
        Gate::authorize('view', $node);

        return $this->status($node);
    }

    public function store(Request $request, Node $node, NodeSetupService $setups): JsonResponse
    {
        Gate::authorize('update', $node);

        return $this->status($setups->request($node, $request->user()), 202);
    }

    public function tenantShow(Tenant $tenant, Node $node): JsonResponse
    {
        Gate::authorize('tenantView', [$node, $tenant]);

        return $this->status($node);
    }

    public function tenantStore(Request $request, Tenant $tenant, Node $node, NodeSetupService $setups): JsonResponse
    {
        Gate::authorize('tenantUpdate', [$node, $tenant]);

        return $this->status($setups->request($node, $request->user()), 202);
    }

    private function status(Node $node, int $code = 200): JsonResponse
    {
        return response()->json(['data' => [
            'node_id' => $node->id,
            'status' => $node->setup_status,
            'request_id' => $node->setup_request_id,
            'requested_by' => $node->setup_requested_by,
            'selection' => $node->setup_selection,
            'fingerprint' => $node->setup_fingerprint,
            'run_id' => $node->setup_run_id,
            'requested_at' => $node->setup_requested_at,
            'started_at' => $node->setup_started_at,
            'finished_at' => $node->setup_finished_at,
            'error' => $node->setup_error,
            'services' => $node->setup_services,
        ]], $code);
    }
}
