<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StorePlanRequest;
use Froxlor\Core\Http\Requests\UpdatePlanRequest;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [Plan::class, $tenant]);

        $tenantPlans = Plan::query()->where('tenant_id', '=', $tenant->id);
        $tenantPlans->with('resources');
        return Response::jsonResourceCollection($tenantPlans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlanRequest $request, Tenant $tenant)
    {
        Gate::authorize('tenantCreate', [Plan::class, $tenant]);

        // get validated data only for ourselves
        $planData = $request->validatedResource();
        // fixed values
        $planData['tenant_id'] = $tenant->id;
        // create resource
        $plan = Plan::query()->create($planData);
        // build up validated data for others
        $eventData = $this->validatedEventData($request);
        // throw event that resource was created and append validated data
        event(new ResourceCreated($plan, $eventData));

        // return resource
        return Response::jsonResource($plan->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tenant $tenant, Plan $plan)
    {
        Gate::authorize('tenantView', [$plan, $tenant]);

        $resourceUsages = $tenant->tenantUsageList;
        $plan->load('resources');

        $plan = $plan->resources->map(function ($resource) use ($resourceUsages) {
            $resource->used = $resourceUsages[$resource->key] ?? 0;
            return $resource;
        });

        return Response::jsonResource($plan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanRequest $request, Tenant $tenant, Plan $plan)
    {
        Gate::authorize('tenantUpdate', [$plan, $tenant]);

        $plan->update($request->validated());
        event(new ResourceUpdated($plan, $this->validatedEventData($request)));

        return Response::jsonResource($plan->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Plan $plan)
    {
        Gate::authorize('tenantDelete', [$plan, $tenant]);
        PlanAssignments::ensureNotAssigned($plan);

        $plan->delete();
        event(new ResourceDeleted($plan, []));

        return response()->noContent();
    }
}
