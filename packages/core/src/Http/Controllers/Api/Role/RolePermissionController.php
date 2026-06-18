<?php

namespace Froxlor\Core\Http\Controllers\Api\Role;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        Gate::authorize('roleViewAny', [Permission::class, $role]);

        if ($request->query('ids_only', false)) {
            return response()->json([
                'data' => $role->permissions()->pluck('permissions.id'),
            ]);
        }

        $allPermissions = Permission::query()->orderBy('key')->get();
        $rolePermissions = $role->permissions()
            ->select(['permissions.id'])
            ->get()
            ->mapWithKeys(fn(Permission $permission) => [
                $permission->id => (bool)$permission->pivot->inheritable,
            ]);

        $permissions = $allPermissions->map(function ($permission) use ($rolePermissions) {
            $permission->assigned = $rolePermissions->has($permission->id);
            $permission->inheritable = $rolePermissions->get($permission->id, false);

            return $permission;
        });

        return JsonResource::collection($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Role $role)
    {
        Gate::authorize('roleCreate', [Permission::class, $role]);

        $data = $request->validate([
            'permission_id' => 'required|string|ulid|exists:permissions,id',
            'inheritable' => 'boolean',
        ]);

        $permission = Permission::findOrFail($data['permission_id']);

        abort_unless($request->user()->canDelegatePermission($permission->key), 403);

        $role->permissions()->attach($permission, ['inheritable' => $data['inheritable'] ?? false]);

        return Response::jsonResourceCollection($role->permissions());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role, Permission $permission)
    {
        Gate::authorize('roleDelete', [$permission, $role]);

        $role->permissions()->detach($permission);

        return Response::jsonResourceCollection($role->permissions());
    }
}
