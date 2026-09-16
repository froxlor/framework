<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreUserRequest;
use Froxlor\Core\Http\Requests\UpdateUserRequest;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\AdministrationGuard;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\RoleAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $rootTenant = Tenant::query()->whereNull('parent_tenant_id')->first();

        return Response::jsonResourceCollection($rootTenant->getAllUsers());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        return \Froxlor\Core\Support\Quota::transaction(function () use ($request) {
            Gate::authorize('create', User::class);

            // get validated data only for ourselves
            $userData = $request->validatedResource();
            // fixed values
            if (empty($userData['tenant_id'])) {
                $targetTenant = $request->user()?->tenants()?->first();
            } else {
                // validate that selected tenant can be used
                $targetTenant = Tenant::query()->where('id', $userData['tenant_id'])->first();
                if (!$targetTenant->exists()) {
                    return $this->errorResponse('No target tenant found for user assignment.', 404);
                }
            }
            if (!$targetTenant) {
                return $this->errorResponse('No target tenant found for user assignment.', 422);
            }
            $this->getNonModelRequestData('tenant_id', $userData);
            $role = $this->getNonModelRequestData('role_id', $userData)
                ?? $this->getNonModelRequestData('role', $userData);
            $plan = $this->getNonModelRequestData('plan_id', $userData)
                ?? $this->getNonModelRequestData('plan', $userData);

            RoleAssignments::ensureAssignable($request->user(), $role, 'role_id', $targetTenant);
            PlanAssignments::ensureAssignableToTenantUser($plan, $targetTenant);

            // create resource
            $user = User::query()->create($userData);
            $targetTenant->users()->attach($user, ['role_id' => $role, 'plan_id' => $plan]);
            // build up validated data for others
            $eventData = $this->validatedEventData($request);
            // throw event that resource was created and append validated data
            event(new ResourceCreated($user, $eventData));
            Audit::notice('user "' . $user->email . '" created', $targetTenant, context: [
                'user_id' => $user->id,
            ]);

            // return resource
            return Response::jsonResource($user->refresh());
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load(['roles', 'tenants', 'environments']);

        $primaryTenant = $user->tenants->first();
        if ($primaryTenant) {
            $user->tenant_id = $primaryTenant->id;
            $user->role_id = $primaryTenant->pivot->role_id ?? null;
            $user->plan_id = $primaryTenant->pivot->plan_id ?? null;
            $user->setRelation('tenant', $primaryTenant);
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
    public function update(UpdateUserRequest $request, User $user)
    {
        return \Froxlor\Core\Support\Quota::transaction(function () use ($request, $user) {
            Gate::authorize('update', $user);

            $userData = $request->validated();
            $tenantId = $this->getNonModelRequestData('tenant_id', $userData);
            $targetTenant = null;
            $roleId = $this->getNonModelRequestData('role_id', $userData)
                ?? $this->getNonModelRequestData('role', $userData);
            $planProvided = $request->has('plan');
            if ($request->has('plan_id')) {
                $planProvided = true;
            }
            $planId = $this->getNonModelRequestData('plan_id', $userData)
                ?? $this->getNonModelRequestData('plan', $userData);

            if ($tenantId) {
                $targetTenant = Tenant::query()->findOrFail($tenantId);
                if ($roleId) {
                    RoleAssignments::ensureAssignable($request->user(), $roleId, 'role_id', $targetTenant);
                }
                if ($planProvided) {
                    PlanAssignments::ensureAssignableToTenantUser($planId, $targetTenant, 'plan_id', $user->id);
                }
            }

            $user->update($userData);

            if ($tenantId) {
                $pivotData = [];
                if (!empty($roleId)) {
                    $pivotData['role_id'] = $roleId;
                }
                if ($planProvided) {
                    $pivotData['plan_id'] = $planId;
                }
                if ($pivotData !== []) {
                    $user->tenants()->syncWithoutDetaching([
                        $tenantId => $pivotData,
                    ]);
                }
            }

            event(new ResourceUpdated($user, $this->validatedEventData($request)));
            Audit::info('user "' . $user->email . '" updated', $targetTenant ?? $user->tenants()->first(), context: [
                'user_id' => $user->id,
            ]);

            return Response::jsonResource($user);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $tenant = AdministrationGuard::run(function () use ($user) {
            Gate::authorize('delete', $user);
            $tenant = $user->tenants()->first();
            $user->delete();
            return $tenant;
        });
        event(new ResourceDeleted($user, []));
        Audit::info('user "' . $user->email . '" deleted', $tenant, context: [
            'user_id' => $user->id,
        ]);

        return response()->noContent();
    }
}
