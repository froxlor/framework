<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Concerns\NormalizesNodeProperties;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreNodeRequest;
use Froxlor\Core\Http\Requests\UpdateNodeRequest;
use Froxlor\Core\Jobs\Node\ExploreNode;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;

class NodeController extends Controller
{
    use NormalizesNodeProperties;

    /**
     * Display a listing of the resource.
     */
    public function index(Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [Node::class, $tenant]);

        return Response::jsonResourceCollection(
            Node::query()
                ->availableForTenant($tenant)
                ->orderBy('name')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNodeRequest $request, Tenant $tenant)
    {
        Gate::authorize('tenantCreate', [Node::class, $tenant]);

        $nodeData = $request->validatedResource();
        $inheritable = (bool)($nodeData['inheritable'] ?? false);
        unset($nodeData['tenant_id'], $nodeData['inheritable']);
        $nodeData['tenant_id'] = $tenant->id;
        $nodeData = $this->normalizeNodeProperties($nodeData);

        $node = Node::query()->create($nodeData);
        $node->tenants()->syncWithoutDetaching([
            $tenant->id => ['inheritable' => $inheritable],
        ]);

        event(new ResourceCreated($node, $this->validatedEventData($request)));

        dispatch(new ExploreNode($node, true));

        return Response::jsonResource($node->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant, Node $node)
    {
        Gate::authorize('tenantView', [$node, $tenant]);

        return Response::jsonResource($node->load(['nodeInterfaces', 'environments.tenant', 'tenants']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNodeRequest $request, Tenant $tenant, Node $node)
    {
        Gate::authorize('tenantUpdate', [$node, $tenant]);

        $node->update($this->normalizeNodeProperties($request->validated(), $node));
        event(new ResourceUpdated($node, $this->validatedEventData($request)));

        return Response::jsonResource($node->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant, Node $node)
    {
        Gate::authorize('tenantDelete', [$node, $tenant]);

        $node->delete();
        event(new ResourceDeleted($node, []));

        return response()->noContent();
    }

}
