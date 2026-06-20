<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StorePlanRequest;
use Froxlor\Core\Http\Requests\UpdatePlanRequest;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Plan::class);

        return Response::jsonResourceCollection(Plan::query()->whereNull('tenant_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlanRequest $request)
    {
        Gate::authorize('create', Plan::class);

        // get validated data only for ourselves
        $planData = $request->validatedResource();
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
    public function show(Plan $plan)
    {
        Gate::authorize('view', $plan);

        return Response::jsonResource($plan->load(['resources']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        Gate::authorize('update', $plan);

        $plan->update($request->validated());
        event(new ResourceUpdated($plan, $this->validatedEventData($request)));

        return Response::jsonResource($plan->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        Gate::authorize('delete', $plan);
        PlanAssignments::ensureNotAssigned($plan);

        $plan->delete();
        event(new ResourceDeleted($plan, []));

        return response()->noContent();
    }
}
