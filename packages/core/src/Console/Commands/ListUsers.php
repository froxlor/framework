<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\EnvUsage;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantUsage;
use Froxlor\Core\Models\TenantUser;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Resource as ResourceUsage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:users
                            {--d|details= : Show one user by ULID, exact email, or exact display name }
                            {--permissiondetails : Show permissions below each role in user details }
                            {--resourcedetails : Show used resource instances below each resource in user details }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect users and their tenant, role, and plan assignments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($identifier = $this->option('details')) {
            return $this->showUser((string)$identifier);
        }

        $this->showUsers();
        return self::SUCCESS;
    }

    /**
     * Show all users with assignment counts.
     */
    private function showUsers(): void
    {
        $users = User::query()
            ->withCount(['tenants', 'roles'])
            ->with('tenants:id')
            ->orderBy('email')
            ->get();

        $this->table(['ID', 'Name', 'Email', 'Tenants', 'Roles', 'Plans'], $users->map(fn(User $user) => [
            $user->id,
            $user->name,
            $user->email,
            $user->tenants_count,
            $user->roles_count + $user->tenants->pluck('pivot.role_id')->filter()->unique()->count(),
            $user->tenants->pluck('pivot.plan_id')->filter()->unique()->count(),
        ]));
    }

    /**
     * Show one user and render tenant assignments as a tree.
     */
    private function showUser(string $identifier): int
    {
        $user = $this->findUser($identifier);

        if (!$user) {
            $this->error('User not found: ' . $identifier);
            return self::FAILURE;
        }

        $globalRoles = $user->roles()->with('permissions')->orderBy('name')->get();
        $tenantAssignments = TenantUser::query()
            ->with([
                'user',
                'tenant.plan.resources',
                'role.permissions',
                'plan.resources',
            ])
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('tenant_id');

        $this->info($user->name . ' (' . $user->id . ')');
        $this->line($user->email);
        $this->newLine();

        $this->line('Global roles');
        if ($globalRoles->isEmpty()) {
            $this->line('`-- none');
        } else {
            $globalRoles->values()->each(function (Role $role, int $index) use ($globalRoles) {
                $isLast = $index === $globalRoles->count() - 1;
                $this->line(($isLast ? '`-- ' : '|-- ') . $this->formatRole($role));
                $this->renderRolePermissions($role, $isLast ? '    ' : '|   ');
            });
        }
        $this->newLine();

        $this->line('Tenant assignments');
        if ($tenantAssignments->isEmpty()) {
            $this->line('`-- none');
            return self::SUCCESS;
        }

        $this->renderTenantTree($tenantAssignments);

        return self::SUCCESS;
    }

    /**
     * Render assigned tenants in their hierarchy, including unassigned ancestors.
     */
    private function renderTenantTree(Collection $tenantAssignments): void
    {
        $assignedTenants = $tenantAssignments->map->tenant;
        $tenantIds = $this->tenantTreeIds($assignedTenants);
        $tenants = Tenant::query()
            ->with('plan.resources')
            ->whereIn('id', $tenantIds)
            ->orderBy('name')
            ->get()
            ->groupBy(fn(Tenant $tenant) => $tenant->parent_tenant_id ?? 'root');

        $roots = $tenants->get('root', new Collection())->values();
        $roots->each(function (Tenant $tenant, int $index) use ($roots, $tenants, $tenantAssignments) {
            $this->renderTenant(
                tenant: $tenant,
                groupedTenants: $tenants,
                assignments: $tenantAssignments,
                prefix: '',
                isLast: $index === $roots->count() - 1,
                isRoot: true,
            );
        });
    }

    /**
     * Render one tenant node and its assignment details.
     */
    private function renderTenant(
        Tenant $tenant,
        Collection $groupedTenants,
        Collection $assignments,
        string $prefix,
        bool $isLast,
        bool $isRoot = false,
    ): void {
        $branch = $isRoot ? '' : ($isLast ? '`-- ' : '|-- ');
        $assignment = $assignments->get($tenant->id);
        $marker = $assignment ? '' : ' (ancestor)';

        $this->line($prefix . $branch . $tenant->name . ' (' . $tenant->id . ')' . $marker);

        $childPrefix = $prefix . ($isRoot ? '' : ($isLast ? '    ' : '|   '));
        $children = $groupedTenants->get($tenant->id, new Collection())->values();

        if ($assignment) {
            $detailPrefix = $childPrefix . ($children->isEmpty() ? '    ' : '|   ');
            $this->renderAssignmentDetails($assignment, $detailPrefix);
        }

        $children->each(function (Tenant $child, int $index) use ($children, $groupedTenants, $assignments, $childPrefix) {
            $this->renderTenant(
                tenant: $child,
                groupedTenants: $groupedTenants,
                assignments: $assignments,
                prefix: $childPrefix,
                isLast: $index === $children->count() - 1,
            );
        });
    }

    /**
     * Render role, plan, and resource information for a tenant assignment.
     */
    private function renderAssignmentDetails(TenantUser $assignment, string $prefix): void
    {
        $effectivePlan = $assignment->plan ?: $assignment->tenant->plan;

        $this->line($prefix . '|-- role: ' . $this->formatRole($assignment->role));
        $this->renderRolePermissions($assignment->role, $prefix . '|   ');
        $this->line($prefix . '|-- plan: ' . $this->formatPlan($effectivePlan, $assignment->plan === null));

        $resources = $effectivePlan?->resources ?? new Collection();
        if ($resources->isEmpty()) {
            $this->line($prefix . '`-- resources: none');
            return;
        }

        $this->line($prefix . '`-- resources');
        $resources->values()->each(function (Resource $resource, int $index) use ($assignment, $resources, $prefix) {
            $branch = $index === $resources->count() - 1 ? '    `-- ' : '    |-- ';
            $this->line($prefix . $branch . $resource->key . ' (' . $resource->type . ', limit ' . $this->formatLimit((int)$resource->pivot->limit) . ', usage ' . $this->usageFor($resource, $assignment) . ')');
            $this->renderResourceInstances(
                resource: $resource,
                assignment: $assignment,
                prefix: $prefix . ($index === $resources->count() - 1 ? '        ' : '    |   '),
            );
        });
    }

    /**
     * Return all assigned tenant IDs plus their ancestors.
     */
    private function tenantTreeIds(Collection $tenants): array
    {
        $ids = [];

        $tenants->each(function (Tenant $tenant) use (&$ids) {
            $current = $tenant;
            while ($current !== null) {
                $ids[$current->id] = $current->id;
                $current = $current->parentTenant;
            }
        });

        return array_values($ids);
    }

    /**
     * Render role permissions when requested for user details.
     */
    private function renderRolePermissions(?Role $role, string $prefix): void
    {
        if (!$this->option('permissiondetails') || !$role) {
            return;
        }

        $permissions = $role->permissions->sortBy('key')->values();

        if ($permissions->isEmpty()) {
            $this->line($prefix . '`-- permissions: none');
            return;
        }

        $this->line($prefix . '`-- permissions');
        $permissions->each(function ($permission, int $index) use ($permissions, $prefix) {
            $branch = $index === $permissions->count() - 1 ? '    `-- ' : '    |-- ';
            $inheritable = (bool)($permission->pivot->inheritable ?? false) ? ', inheritable' : '';
            $this->line($prefix . $branch . $permission->key . ' (' . $permission->name . $inheritable . ')');
        });
    }

    /**
     * Resolve a user by ULID, exact email, or exact display name.
     */
    private function findUser(string $identifier): ?User
    {
        $user = User::query()
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('company_name', $identifier)
            ->first();

        if ($user) {
            return $user;
        }

        return User::query()
            ->get()
            ->first(fn(User $candidate) => $candidate->name === $identifier);
    }

    /**
     * Format a role with permission count.
     */
    private function formatRole(?Role $role): string
    {
        if (!$role) {
            return 'none';
        }

        return $role->name . ' (' . $role->id . ', ' . $role->permissions->count() . ' permissions)';
    }

    /**
     * Format a plan line.
     */
    private function formatPlan(?Plan $plan, bool $inherited): string
    {
        if (!$plan) {
            return 'none';
        }

        return $plan->name . ' (' . $plan->id . ')' . ($inherited ? ' inherited from tenant' : '');
    }

    /**
     * Convert stored plan limits to readable CLI output.
     */
    private function formatLimit(int $limit): string
    {
        return $limit === -1 ? 'unlimited' : (string)$limit;
    }

    /**
     * Count current user usage for a plan resource within the tenant assignment.
     */
    private function usageFor(Resource $resource, TenantUser $assignment): int
    {
        if ($resource->type === 'environment') {
            return EnvUsage::query()
                ->where('resource_key', $resource->key)
                ->where('user_id', $assignment->user_id)
                ->whereHas('environment', fn($query) => $query->where('tenant_id', $assignment->tenant_id))
                ->count();
        }

        return ResourceUsage::getUsage($assignment->tenant, $resource->model_type, $assignment->user);
    }

    /**
     * Render concrete usage records for a plan resource when requested.
     */
    private function renderResourceInstances(Resource $resource, TenantUser $assignment, string $prefix): void
    {
        if (!$this->option('resourcedetails')) {
            return;
        }

        $usages = $this->usageRecordsFor($resource, $assignment);

        if ($usages->isEmpty()) {
            $this->line($prefix . '`-- used instances: none');
            return;
        }

        $this->line($prefix . '`-- used instances');
        $usages->values()->each(function ($usage, int $index) use ($usages, $prefix, $resource) {
            $branch = $index === $usages->count() - 1 ? '    `-- ' : '    |-- ';
            $this->line($prefix . $branch . $this->formatResourceInstance($resource, $usage->resource_id));
        });
    }

    /**
     * Return usage records for a resource in this tenant assignment.
     */
    private function usageRecordsFor(Resource $resource, TenantUser $assignment): Collection
    {
        if ($resource->type === 'environment') {
            return EnvUsage::query()
                ->where('resource_key', $resource->key)
                ->where('user_id', $assignment->user_id)
                ->whereHas('environment', fn($query) => $query->where('tenant_id', $assignment->tenant_id))
                ->orderBy('resource_id')
                ->get();
        }

        return TenantUsage::query()
            ->where('tenant_id', $assignment->tenant_id)
            ->where('resource_key', $resource->key)
            ->where('user_id', $assignment->user_id)
            ->orderBy('resource_id')
            ->get();
    }

    /**
     * Format one used resource instance.
     */
    private function formatResourceInstance(Resource $resource, string $resourceId): string
    {
        $modelClass = Relation::getMorphedModel($resource->key)
            ?? (class_exists($resource->model_type) ? $resource->model_type : null);

        if (!$modelClass || !is_subclass_of($modelClass, Model::class)) {
            return $resourceId;
        }

        $model = $modelClass::query()->find($resourceId);

        if (!$model) {
            return $resourceId . ' (missing)';
        }

        $label = $model->name
            ?? $model->email
            ?? $model->hostname
            ?? $model->key
            ?? class_basename($modelClass);

        return $label . ' (' . $resourceId . ')';
    }
}
