<?php

namespace Froxlor\Core\Http\Controllers\Api\Role;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * View permissions assigned to roles,
 * adjust role permissions on a global basis
 */
class RolePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Role $role)
    {
        //Gate::authorize('roleViewAny', [Permission::class, $role]);

        if ($request->query('ids_only', false)) {
            return Response::jsonResourceCollection($role->permissions()->pluck('permissions.id'));
        }

        return Response::jsonResourceCollection($role->permissions());
/*
        $allPermissions = Permission::query()->orderBy('key')->get();
        $rolePermissions = $role->permissions()->select(['permissions.id'])->get()->mapWithKeys(function ($item) {
            return [$item['id'] => (bool)$item['pivot']['inheritable']];
        })->toArray();

        $permissions = $allPermissions->map(function ($permission) use ($rolePermissions) {
            $permission->assigned = array_key_exists($permission->id, $rolePermissions);
            $permission->inheritable = $rolePermissions[$permission->id] ?? false;
            return $permission;
        });

        return response()->json([
            'data' => $permissions
        ]);
*/
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Role $role)
    {
        Gate::authorize('roleCreate', [Permission::class, $role]);

        $data = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'inheritable' => 'boolean',
        ]);

        $permission = Permission::findOrFail($data['permission_id']);

        $role->permissions()->attach($permission, ['inheritable' => $data['inheritable'] ?? false]);

        return Response::jsonResource($role->permissions());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role, Permission $permission)
    {
        Gate::authorize('roleDelete', [$permission, $role]);

        $role->permissions()->detach($permission);

        return Response::jsonResource($role->permissions());
    }
}
