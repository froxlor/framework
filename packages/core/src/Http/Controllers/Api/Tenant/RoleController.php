<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\Tenant\StoreTenantRoleRequest;
use Froxlor\Core\Http\Requests\UpdateRoleRequest;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [Role::class, $tenant]);

        $roles = Role::query()
            ->with(['permissions'])
            ->where(function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->orWhereNull('tenant_id');
            })
            // order by custom-roles first, then global ones
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('name');

        return Response::jsonResourceCollection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRoleRequest $request, Tenant $tenant)
    {
        Gate::authorize('tenantCreate', [Role::class, $tenant]);

        // get validated data only for ourselves
        $roleData = $request->validatedResource();
        // fixed values
        $roleData['tenant_id'] = $tenant->id;
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
    public function show(Request $request, Tenant $tenant, Role $role)
    {
        Gate::authorize('tenantView', [$role, $tenant]);

        return Response::jsonResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Tenant $tenant, Role $role)
    {
        Gate::authorize('tenantUpdate', [$role, $tenant]);

        $role->update($request->validated());

        return Response::jsonResource($role->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Role $role)
    {
        Gate::authorize('tenantDelete', [$role, $tenant]);

        $role->delete();

        return response()->noContent();
    }
}
