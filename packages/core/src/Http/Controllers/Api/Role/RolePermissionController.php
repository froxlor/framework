<?php

namespace Froxlor\Core\Http\Controllers\Api\Role;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\RoleAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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

        abort_unless(RoleAssignments::canDelegate($request->user(), $permission->key), 403);

        $role->permissions()->syncWithoutDetaching([
            $permission->id => ['inheritable' => $data['inheritable'] ?? false],
        ]);

        Audit::info('permission "' . $permission->key . '" assigned to role "' . $role->name . '"', $role->tenant, context: [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'permission_key' => $permission->key,
            'inheritable' => (bool)($data['inheritable'] ?? false),
        ]);

        return Response::jsonResourceCollection($role->permissions());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role, Permission $permission)
    {
        Gate::authorize('roleDelete', [$permission, $role]);

        abort_unless(RoleAssignments::canDelegate($request->user(), $permission->key), 403);

        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            throw ValidationException::withMessages([
                'permission_id' => 'The selected permission is not assigned to this role.',
            ]);
        }

        $role->permissions()->detach($permission);

        Audit::info('permission "' . $permission->key . '" removed from role "' . $role->name . '"', $role->tenant, context: [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'permission_key' => $permission->key,
        ]);

        return Response::jsonResourceCollection($role->permissions());
    }
}
