<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant\Role;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\Response;
use Froxlor\Core\Support\RoleAssignments;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{
    /**
     * Display every available permission and mark permissions assigned to the tenant role.
     */
    public function index(Request $request, Tenant $tenant, Role $role)
    {
        Gate::authorize('tenantRoleViewAny', [Permission::class, $tenant, $role]);

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
     * Assign a permission to a tenant-owned role when the actor may delegate it.
     */
    public function store(Request $request, Tenant $tenant, Role $role)
    {
        Gate::authorize('tenantRoleCreate', [Permission::class, $tenant, $role]);

        $data = $request->validate([
            'permission_id' => 'required|string|ulid|exists:permissions,id',
            'inheritable' => 'boolean',
        ]);

        $permission = Permission::findOrFail($data['permission_id']);

        abort_unless(RoleAssignments::canDelegate($request->user(), $permission->key, $tenant), 403);

        $role->permissions()->syncWithoutDetaching([
            $permission->id => ['inheritable' => $data['inheritable'] ?? false],
        ]);

        Audit::notice('permission "' . $permission->key . '" assigned to role "' . $role->name . '"', $tenant, context: [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'permission_key' => $permission->key,
            'inheritable' => (bool)($data['inheritable'] ?? false),
        ]);

        return Response::jsonResourceCollection($role->permissions());
    }

    /**
     * Remove a permission from a tenant-owned role when the actor may delegate it.
     */
    public function destroy(Request $request, Tenant $tenant, Role $role, Permission $permission)
    {
        Gate::authorize('tenantRoleDelete', [$permission, $tenant, $role]);

        abort_unless(RoleAssignments::canDelegate($request->user(), $permission->key, $tenant), 403);

        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            throw ValidationException::withMessages([
                'permission_id' => 'The selected permission is not assigned to this role.',
            ]);
        }

        $role->permissions()->detach($permission);

        Audit::info('permission "' . $permission->key . '" removed from role "' . $role->name . '"', $tenant, context: [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'permission_key' => $permission->key,
        ]);

        return Response::jsonResourceCollection($role->permissions());
    }
}
