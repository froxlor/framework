<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreTenantRequest;
use Froxlor\Core\Http\Requests\UpdateTenantRequest;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Tenant::class);

        return Response::jsonResourceCollection(Tenant::query());
        //return Response::jsonResourceCollection($request->user()->tenants()->with(['plan']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        // get validated data only for ourselves
        $tenantData = $request->validatedResource();
        $nodes = $tenantData['nodes'] ?? [];
        unset($tenantData['nodes']);
        $parentTenant = Tenant::query()->find($tenantData['parent_tenant_id']);

        Gate::authorize('create', [Tenant::class, $parentTenant]);

        // create resource
        $tenant = Tenant::query()->create($tenantData);
        foreach ($nodes as $nodeData) {
            $node = Node::query()->findOrFail($nodeData['id']);
            abort_unless($node->isInheritableByTenant($parentTenant), 422, 'The selected node cannot be inherited by this tenant.');

            $tenant->nodes()->syncWithoutDetaching([
                $node->id => ['inheritable' => (bool)($nodeData['inheritable'] ?? false)],
            ]);
        }
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new ResourceCreated($tenant, $eventData));

        // return resource
        return Response::jsonResource($tenant->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        Gate::authorize('view', $tenant);

        return Response::jsonResource($tenant->load('plan')->append('tenant_usage_list'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        Gate::authorize('update', $tenant);

        $tenant->update($request->validated());

        return Response::jsonResource($tenant->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        Gate::authorize('delete', $tenant);

        $tenant->delete();

        return response()->noContent();
    }
}
