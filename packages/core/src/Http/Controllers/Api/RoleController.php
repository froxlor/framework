<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreRoleRequest;
use Froxlor\Core\Http\Requests\UpdateRoleRequest;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::query()
            ->whereNull('tenant_id')
            ->orderBy('name');

        return Response::jsonResourceCollection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        Gate::authorize('create', Role::class);

        // get validated data only for ourselves
        $roleData = $request->validatedResource();
        // create resource
        $role = Role::query()->create($roleData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new ResourceCreated($role, $eventData));

        // return resource
        return Response::jsonResource($role->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        Gate::authorize('view', $role);

        return Response::jsonResource($role->load(['permissions', 'users']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        Gate::authorize('update', $role);

        $role->update($request->validated());

        return Response::jsonResource($role->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        Gate::authorize('delete', $role);

        $role->delete();

        return response()->noContent();
    }
}
