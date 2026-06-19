<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ListRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:roles
                            {--d|details= : Show one role by ULID or exact name }
                            {--p|permissions : List all available permissions instead of roles }
                            {--used-by= : Show roles using a permission key }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect roles, assigned permissions, and permission usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($permissionKey = $this->option('used-by')) {
            return $this->showRolesUsingPermission((string)$permissionKey);
        }

        if ($this->option('permissions')) {
            $this->showPermissions();
            return self::SUCCESS;
        }

        if ($identifier = $this->option('details')) {
            return $this->showRole((string)$identifier);
        }

        $this->showRoles();
        return self::SUCCESS;
    }

    /**
     * Show all roles with the number of attached permissions.
     */
    private function showRoles(): void
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        $this->table(['ID', 'Name', 'Tenant ID', 'Permissions'], $roles->map(fn(Role $role) => [
            $role->id,
            $role->name,
            $role->tenant_id ?? 'global',
            $role->permissions_count,
        ]));
    }

    /**
     * Show all roles that contain the given permission key.
     */
    private function showRolesUsingPermission(string $permissionKey): int
    {
        $permission = Permission::query()
            ->where('key', $permissionKey)
            ->with(['roles' => fn($query) => $query->orderBy('name')])
            ->first();

        if (!$permission) {
            $this->error('Permission not found: ' . $permissionKey);
            return self::FAILURE;
        }

        $this->info($permission->key . ' (' . $permission->id . ')');
        $this->line($permission->name);
        $this->newLine();

        $this->table(['Role ID', 'Role', 'Tenant ID', 'Inheritable'], $permission->roles->map(fn(Role $role) => [
            $role->id,
            $role->name,
            $role->tenant_id ?? 'global',
            (bool)$role->pivot->inheritable ? 'yes' : 'no',
        ]));

        if ($permission->roles->isEmpty()) {
            $this->warn('No roles use permission key: ' . $permissionKey);
        }

        return self::SUCCESS;
    }

    /**
     * Show all permissions with the number of roles using each permission.
     */
    private function showPermissions(): void
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('key')
            ->get();

        $this->table(['ID', 'Key', 'Name', 'Roles'], $permissions->map(fn(Permission $permission) => [
            $permission->id,
            $permission->key,
            $permission->name,
            $permission->roles_count,
        ]));
    }

    /**
     * Show one role and list its attached permissions with inheritance metadata.
     */
    private function showRole(string $identifier): int
    {
        $role = $this->findRole($identifier);

        if (!$role) {
            $this->error('Role not found: ' . $identifier);
            return self::FAILURE;
        }

        $this->info($role->name . ' (' . $role->id . ')');
        $this->line('Tenant: ' . ($role->tenant_id ?? 'global'));
        $this->newLine();

        $permissions = $role->permissions()
            ->orderBy('permissions.key')
            ->get();

        $this->table(['Permission', 'Name', 'Inheritable'], $permissions->map(fn(Permission $permission) => [
            $permission->key,
            $permission->name,
            (bool)$permission->pivot->inheritable ? 'yes' : 'no',
        ]));

        return self::SUCCESS;
    }

    /**
     * Resolve a role by ULID or exact role name.
     */
    private function findRole(string $identifier): ?Role
    {
        return Role::query()
            ->where('id', $identifier)
            ->orWhere(fn(Builder $query) => $query->where('name', $identifier))
            ->first();
    }
}
