<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StorePlanRequest;
use Froxlor\Core\Http\Requests\UpdatePlanRequest;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       // Gate::authorize('viewAny', Plan::class);

        return Response::jsonResourceCollection(Plan::query()->whereNull('tenant_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlanRequest $request)
    {
        //Gate::authorize('create', Plan::class);

        // get validated data only for ourselves
        $planData = $request->validatedResource();
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
    public function show(Plan $plan)
    {
        //Gate::authorize('view', Plan::class);

        return Response::jsonResource($plan->load(['resources']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        Gate::authorize('update', Plan::class);

        $plan->update($request->validated());

        return Response::jsonResource($plan->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        Gate::authorize('delete', Plan::class);

        $plan->delete();

        return response()->noContent();
    }
}
