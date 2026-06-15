<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Gate::authorize('viewAny', Node::class);

        return Response::jsonResourceCollection(Node::query()->orderBy('name'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNodeRequest $request)
    {
        //Gate::authorize('create', Node::class);

        // get validated data only for ourselves
        $nodeData = $request->validatedResource();
        $inheritable = (bool)($nodeData['inheritable'] ?? false);
        unset($nodeData['inheritable']);

        $tenant = null;
        if (!empty($nodeData['tenant_id'])) {
            $tenant = Tenant::query()->findOrFail($nodeData['tenant_id']);
            Gate::authorize('view', $tenant);
        }

        // create resource
        $node = Node::query()->create($nodeData);
        if ($tenant !== null) {
            $node->tenants()->syncWithoutDetaching([
                $tenant->id => ['inheritable' => $inheritable],
            ]);
        }
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new ResourceCreated($node, $eventData));
        // run explore-node job
        dispatch(new ExploreNode($node, true));

        // return resource
        return Response::jsonResource($node->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Node $node)
    {
        //Gate::authorize('view', Node::class);

        return Response::jsonResource($node->load(['nodeInterfaces', 'environments.tenant']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNodeRequest $request, Node $node)
    {
        //Gate::authorize('update', Node::class);

        $node->update($request->validated());

        return Response::jsonResource($node);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Node $node)
    {
        //Gate::authorize('delete', Node::class);

        $node->delete();

        return response()->noContent();
    }
}
