<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\StoreEnvironmentRequest;
use Froxlor\Core\Http\Requests\UpdateEnvironmentRequest;
use Froxlor\Core\Jobs\Environment\CreateEnvironment;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Traits\TenantAccessPermission;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    use TenantAccessPermission;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        //Gate::authorize('tenantViewAny', [Environment::class, $tenant]);

        return Response::jsonResourceCollection($tenant->environments());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnvironmentRequest $request, Tenant $tenant)
    {
        // get validated data only for ourselves
        $envData = $request->validatedResource();
        // fixed values
        $envData['tenant_id'] = $tenant->id;
        // non-model values
        $node_id = $this->getNonModelRequestData('node_id', $envData);
        // create resource
        $env = Environment::query()->create($envData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new ResourceCreated($env, $eventData));
        // connect to node and create environment if given
        if (!empty($node_id)) {
            $node = Node::query()->findOrFail($node_id);
            dispatch(new CreateEnvironment($env->refresh(), $node));
        }

        // return resource
        return Response::jsonResource($env->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tenant $tenant, Environment $environment)
    {
        // Gate::authorize('tenantView', [$environment, $tenant]);

        return Response::jsonResource($environment->load(['plan', 'users'])->append('env_usage_list'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEnvironmentRequest $request, Tenant $tenant, Environment $environment)
    {
        $envData = $request->validated();
        $nodeId = $this->getNonModelRequestData('node_id', $envData);

        $environment->update($envData);

        if (!empty($nodeId)) {
            $node = Node::query()->findOrFail($nodeId);
            dispatch(new CreateEnvironment($environment->refresh(), $node));
        }

        return Response::jsonResource($environment->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment)
    {
        $environment->delete();

        return response()->noContent();
    }
}
