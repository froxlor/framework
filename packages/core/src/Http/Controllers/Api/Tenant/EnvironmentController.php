<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\StoreEnvironmentRequest;
use Froxlor\Core\Http\Requests\UpdateEnvironmentRequest;
use Froxlor\Core\Jobs\Environment\CreateEnvironment;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EnvironmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [Environment::class, $tenant]);

        return Response::jsonResourceCollection($tenant->environments());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnvironmentRequest $request, Tenant $tenant)
    {
        Gate::authorize('tenantCreate', [Environment::class, $tenant]);

        // get validated data only for ourselves
        $envData = $request->validatedResource();
        // fixed values
        $envData['tenant_id'] = $tenant->id;
        // non-model values
        $node_id = $this->getNonModelRequestData('node_id', $envData);
        PlanAssignments::ensurePlanAvailableForTenant($envData['plan_id'] ?? null, $tenant);
        // create resource
        $env = Environment::query()->create($envData);
        // build up validated data for others
        $eventData = $this->validatedEventData($request);
        // throw event that resource was created and append validated data
        event(new ResourceCreated($env, $eventData));
        // connect to node and create environment if given
        if (!empty($node_id)) {
            $node = $this->nodeForTenant($node_id, $tenant);
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
        Gate::authorize('tenantView', [$environment, $tenant]);

        return Response::jsonResource($environment->load(['plan', 'users'])->append('env_usage_list'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEnvironmentRequest $request, Tenant $tenant, Environment $environment)
    {
        Gate::authorize('tenantUpdate', [$environment, $tenant]);

        $envData = $request->validated();
        $nodeId = $this->getNonModelRequestData('node_id', $envData);
        PlanAssignments::ensurePlanAvailableForTenant($envData['plan_id'] ?? null, $tenant);

        $environment->update($envData);
        event(new ResourceUpdated($environment, $this->validatedEventData($request)));

        if (!empty($nodeId)) {
            $node = $this->nodeForTenant($nodeId, $tenant);
            dispatch(new CreateEnvironment($environment->refresh(), $node));
        }

        return Response::jsonResource($environment->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment)
    {
        Gate::authorize('tenantDelete', [$environment, $tenant]);

        $environment->delete();
        event(new ResourceDeleted($environment, []));

        return response()->noContent();
    }

    /**
     * Resolve a node and ensure it is usable in the current tenant context.
     *
     * A tenant environment may only be provisioned on nodes that are directly
     * owned by the tenant or explicitly available to it through node inheritance.
     *
     * @throws ValidationException
     */
    private function nodeForTenant(string $nodeId, Tenant $tenant): Node
    {
        $node = Node::query()->findOrFail($nodeId);

        if (!$node->isAvailableForTenant($tenant)) {
            throw ValidationException::withMessages([
                'node_id' => trans('validation.exists', ['attribute' => 'node_id']),
            ]);
        }

        return $node;
    }

}
