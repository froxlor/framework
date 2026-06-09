<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StorePermissionRequest;
use Froxlor\Core\Http\Requests\UpdatePermissionRequest;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       // Gate::authorize('viewAny', Permission::class);

        return Response::jsonResourceCollection(Permission::query());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        Gate::authorize('create', Permission::class);

        $permission = Permission::query()->create($request->validated());

        return Response::jsonResource($permission->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        Gate::authorize('view', Permission::class);

        return Response::jsonResource($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        Gate::authorize('update', Permission::class);

        $permission->update($request->validated());

        return Response::jsonResource($permission->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('delete', Permission::class);

        $permission->delete();

        return response()->noContent();
    }
}
