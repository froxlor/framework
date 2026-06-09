<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant\Environment;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\Environment\StoreEnvironmentPlanRequest;
use Froxlor\Core\Http\Requests\UpdatePlanRequest;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Traits\TenantAccessPermission;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlansController extends Controller
{
    use TenantAccessPermission;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant, Environment $environment)
    {
        //Gate::authorize('tenantEnvViewAny', [Plan::class, $tenant, $environment]);

        return Response::jsonResourceCollection(Plan::query()->where('tenant_id', $tenant->id)->where('type', 'environment'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnvironmentPlanRequest $request, Tenant $tenant, Environment $environment)
    {
        // get validated data only for ourselves
        $planData = $request->validatedResource();
        // fixed values
        $planData['tenant_id'] = $tenant->id;
        $planData['type'] = 'environment';
        // create resource
        $plan = Plan::query()->create($planData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new ResourceCreated($plan, $eventData));

        // return resource
        return Response::jsonResource($plan->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tenant $tenant, Environment $environment, Plan $plan)
    {
        //Gate::authorize('tenantEnvView', [$plan, $tenant, $environment]);

        return Response::jsonResource($plan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanRequest $request, Tenant $tenant, Environment $environment, Plan $plan)
    {
        $plan->update($request->validated());

        return Response::jsonResource($plan->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment, Plan $plan)
    {
        $plan->delete();

        return response()->noContent();
    }
}
