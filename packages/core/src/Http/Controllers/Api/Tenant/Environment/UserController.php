<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant\Environment;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\Environment\StoreEnvironmentUserRequest;
use Froxlor\Core\Http\Requests\UpdateUserRequest;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\RoleAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant, Environment $environment)
    {
        Gate::authorize('tenantEnvViewAny', [User::class, $tenant, $environment]);

        return Response::jsonResourceCollection($environment->users());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnvironmentUserRequest $request, Tenant $tenant, Environment $environment)
    {
        Gate::authorize('tenantEnvCreate', [User::class, $tenant, $environment]);

        if ($environment->userHasResourceAvailable($request->user(), User::getResourceKey())) {

            // get validated data only for ourselves
            $userData = $request->validatedResource();
            $tenant_role = $this->getNonModelRequestData('tenant_role', $userData);
            $tenant_plan = $this->getNonModelRequestData('tenant_plan', $userData);
            $env_role = $this->getNonModelRequestData('environment_role', $userData);
            $env_plan = $this->getNonModelRequestData('environment_plan', $userData);

            RoleAssignments::ensureAssignable($request->user(), $tenant_role, 'tenant_role', $tenant);
            RoleAssignments::ensureAssignable($request->user(), $env_role, 'environment_role', $tenant, $environment);

            // create resource
            $user = User::query()->create($userData);
            $tenant->users()->attach($user, ['role_id' => $tenant_role, 'plan_id' => $tenant_plan]);
            // connect environment
            $user->environments()->attach($environment, ['role_id' => $env_role, 'plan_id' => $env_plan]);
            // build up validated data for others
            $eventData = $this->validatedEventData($request);
            // throw event that resource was created and append validated data
            event(new ResourceCreated($user, $eventData));

            Audit::log('user "' . $user->email . '" created', $tenant, $environment);
            // return resource
            return Response::jsonResource($user->refresh());
        }
        return response()->json(['error' => 'Unsufficient resources'], 406);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tenant $tenant, Environment $environment, User $user)
    {
        Gate::authorize('tenantEnvView', [$user, $tenant, $environment]);

        return Response::jsonResource($user->load(['roles', 'envUsages']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, Tenant $tenant, Environment $environment, User $user)
    {
        Gate::authorize('tenantEnvUpdate', [$user, $tenant, $environment]);

        $user->update($request->validated());
        event(new ResourceUpdated($user, $this->validatedEventData($request)));

        return Response::jsonResource($user->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment, User $user)
    {
        Gate::authorize('tenantEnvDelete', [$user, $tenant, $environment]);

        $environment->users()->detach($user);
        event(new ResourceDeleted($user, []));

        return response()->noContent();
    }
}
