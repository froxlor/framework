<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\StoreTenantUserRequest;
use Froxlor\Core\Http\Requests\UpdateUserRequest;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\RoleAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [User::class, $tenant]);

        return Response::jsonResourceCollection($tenant->users());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantUserRequest $request, Tenant $tenant)
    {
        Gate::authorize('tenantCreate', [User::class, $tenant]);

        if ($tenant->userHasResourceAvailable($request->user(), User::getResourceKey())) {

            // get validated data only for ourselves
            $userData = $request->validatedResource();
            $role = $this->getNonModelRequestData('role_id', $userData)
                ?? $this->getNonModelRequestData('role', $userData);
            $plan = $this->getNonModelRequestData('plan_id', $userData)
                ?? $this->getNonModelRequestData('plan', $userData);

            RoleAssignments::ensureAssignable($request->user(), $role, 'role_id', $tenant);
            $this->ensurePlanCanBeAssignedToTenant($plan, $tenant);

            // create resource
            $user = User::query()->create($userData);
            $tenant->users()->attach($user, ['role_id' => $role, 'plan_id' => $plan]);
            // build up validated data for others
            $eventData = $this->validatedEventData($request);
            // throw event that resource was created and append validated data
            event(new ResourceCreated($user, $eventData));

            Audit::log('user "' . $user->email . '" created', $tenant);
            // return resource
            return Response::jsonResource($user->refresh());
        }
        return response()->json(['error' => 'Unsufficient resources'], 406);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tenant $tenant, User $user)
    {
        Gate::authorize('tenantView', [$user, $tenant]);

        $user->load(['roles', 'environments', 'tenants']);
        $pivot = $user->tenants->firstWhere('id', $tenant->id)?->pivot;
        if ($pivot) {
            $user->tenant_id = $tenant->id;
            $user->role_id = $pivot->role_id;
            $user->plan_id = $pivot->plan_id;
            $user->setRelation('tenant', $tenant);
            $user->setRelation('role', $user->role_id
                ? Role::query()->find($user->role_id)
                : null);
            $user->setRelation('plan', $user->plan_id
                ? Plan::query()->find($user->plan_id)
                : null);
        }

        return Response::jsonResource($user);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, Tenant $tenant, User $user)
    {
        Gate::authorize('tenantUpdate', [$user, $tenant]);

        $userData = $request->validated();
        unset($userData['tenant_id']);
        $roleId = $this->getNonModelRequestData('role_id', $userData)
            ?? $this->getNonModelRequestData('role', $userData);
        $planProvided = $request->has('plan');
        if ($request->has('plan_id')) {
            $planProvided = true;
        }
        $planId = $this->getNonModelRequestData('plan_id', $userData)
            ?? $this->getNonModelRequestData('plan', $userData);

        RoleAssignments::ensureAssignable($request->user(), $roleId, 'role_id', $tenant);
        $this->ensurePlanCanBeAssignedToTenant($planId, $tenant);

        $user->update($userData);

        $pivotData = [];
        if (!empty($roleId)) {
            $pivotData['role_id'] = $roleId;
        }
        if ($planProvided) {
            $pivotData['plan_id'] = $planId;
        }
        if ($pivotData !== []) {
            $user->tenants()->syncWithoutDetaching([
                $tenant->id => $pivotData,
            ]);
        }

        event(new ResourceUpdated($user, $this->validatedEventData($request)));

        return Response::jsonResource($user->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, User $user)
    {
        Gate::authorize('tenantDelete', [$user, $tenant]);

        $tenant->users()->detach($user);
        event(new ResourceDeleted($user, []));

        return response()->json(['message' => 'User removed from environment successfully'], 200);
    }

    private function ensurePlanCanBeAssignedToTenant(?string $planId, Tenant $tenant): void
    {
        if (empty($planId)) {
            return;
        }

        $plan = Plan::query()->findOrFail($planId);

        if ($plan->tenant_id !== null && $plan->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages([
                'plan_id' => 'The selected plan is not available for this tenant.',
            ]);
        }
    }
}
