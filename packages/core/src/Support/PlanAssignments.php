<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantResourceReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanAssignments
{
    /**
     * Ensure that a plan can be assigned as an optional tenant-user limit plan.
     *
     * Tenant users inherit the tenant plan when no explicit plan is assigned. When an
     * explicit plan is assigned, it must be available in the tenant context and its
     * resources must not exceed the tenant's own plan.
     *
     * @throws ValidationException
     */
    public static function ensureAssignableToTenantUser(?string $planId, Tenant $tenant, string $field = 'plan_id'): void
    {
        if (empty($planId)) {
            return;
        }

        $plan = Plan::query()->with('resources')->findOrFail($planId);

        if (!$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, 'The selected plan is not available for this tenant.');
        }

        self::ensureHasEnabledResources($plan, $field, 'The selected plan does not contain enabled resources.');
        self::ensureWithinParentPlan($plan, $tenant->plan, $field);
    }

    /**
     * Ensure that a plan can be assigned as an optional environment-user limit plan.
     *
     * Environment users inherit the environment plan when no explicit plan is assigned.
     * An explicit environment-user plan must stay within the environment's total plan so
     * per-user limits never exceed the scope's total resource budget.
     *
     * @throws ValidationException
     */
    public static function ensureAssignableToEnvironmentUser(
        ?string $planId,
        Tenant $tenant,
        Environment $environment,
        string $field = 'environment_plan',
    ): void {
        if (empty($planId)) {
            return;
        }

        $plan = Plan::query()->with('resources')->findOrFail($planId);

        if (!$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, 'The selected plan is not available for this environment.');
        }

        self::ensureHasEnabledResources($plan, $field, 'The selected plan does not contain enabled resources.');
        self::ensureWithinParentPlan($plan, self::environmentParentPlan($environment), $field);
    }

    /**
     * Ensure a plan is not used before deletion.
     *
     * Plans are quota-bearing objects. Deleting an assigned plan would change resource
     * enforcement for tenants, environments, or user pivots implicitly, so API deletes
     * reject used plans with a validation response.
     *
     * @throws ValidationException
     */
    public static function ensureNotAssigned(Plan $plan): void
    {
        $assignments = [
            'tenants' => DB::table('tenants')->where('plan_id', $plan->id)->count(),
            'environments' => DB::table('environments')->where('plan_id', $plan->id)->count(),
            'tenant users' => DB::table('tenant_user')->where('plan_id', $plan->id)->count(),
            'environment users' => DB::table('environment_user')->where('plan_id', $plan->id)->count(),
            'tenant reservations' => DB::table('tenant_resource_reservations')->where('plan_id', $plan->id)->count(),
        ];

        $usedBy = collect($assignments)
            ->filter()
            ->keys()
            ->implode(', ');

        if ($usedBy !== '') {
            throw self::validationException('plan', 'The plan is still assigned to ' . $usedBy . '.');
        }
    }

    /**
     * Assign or update one resource limit and revalidate every existing plan assignment.
     *
     * Used plans stay editable, but the resulting plan must still fit all places where
     * it is assigned. For child tenants, reservations are synchronized after successful
     * validation so parent budgets immediately reflect the changed limits.
     *
     * @throws ValidationException
     */
    public static function updatePlanResourceLimit(Plan $plan, Resource $resource, int $limit, ?Tenant $tenant = null): void
    {
        DB::transaction(function () use ($plan, $resource, $limit, $tenant): void {
            self::lockPlanAssignments($plan);
            self::ensureResourceCanBeAttached($plan, $resource, $limit, $tenant, 'limit');

            $plan->resources()->syncWithoutDetaching([
                $resource->id => ['limit' => $limit],
            ]);

            self::ensureAssignedPlanRemainsValid($plan->refresh());
            self::syncReservationsForAssignedTenants($plan->refresh());
        });
    }

    /**
     * Remove one resource from a plan and revalidate every existing plan assignment.
     *
     * Removing a resource is equivalent to setting its limit to unavailable. It is only
     * allowed when no current assignment or reservation still depends on that resource.
     *
     * @throws ValidationException
     */
    public static function removePlanResource(Plan $plan, Resource $resource): void
    {
        DB::transaction(function () use ($plan, $resource): void {
            self::lockPlanAssignments($plan);

            $plan->resources()->detach($resource);

            self::ensureAssignedPlanRemainsValid($plan->refresh());
            self::syncReservationsForAssignedTenants($plan->refresh());
        });
    }

    /**
     * Ensure a resource can be attached to the given plan with the requested limit.
     *
     * For tenant-owned plans, resource limits must stay within the owning
     * tenant's assigned plan so tenants cannot create child plans that grant more
     * capacity than they own.
     *
     * @throws ValidationException
     */
    public static function ensureResourceCanBeAttached(
        Plan $plan,
        Resource $resource,
        int $limit,
        ?Tenant $tenant = null,
        string $field = 'resource_id',
    ): void {
        if ($tenant === null || $limit === 0) {
            return;
        }

        $parentPlan = $tenant->plan;
        if ($parentPlan === null) {
            throw self::validationException('limit', 'The resource cannot be assigned without a parent plan.');
        }

        $parentResource = $parentPlan->resources()
            ->where('resources.key', $resource->key)
            ->where('resources.type', $resource->type)
            ->first();
        $parentLimit = $parentResource === null ? null : (int)$parentResource->pivot->limit;

        if ($parentLimit === null || $parentLimit === 0) {
            throw self::validationException('limit', 'The selected resource is not available in the parent plan.');
        }

        if ($limit === -1 && $parentLimit !== -1) {
            throw self::validationException('limit', 'The selected resource grants unlimited usage above the parent plan.');
        }

        if ($limit > 0 && $parentLimit !== -1 && $limit > $parentLimit) {
            throw self::validationException('limit', 'The selected resource limit is above the parent plan.');
        }
    }

    /**
     * Ensure every enabled resource in a child plan fits into the parent plan.
     *
     * Limit semantics are: `0` means no access, `-1` means unlimited, and positive
     * values are finite limits. A child plan may omit a resource or set it to `0`, but it
     * cannot grant a resource missing from the parent, grant unlimited unless the parent
     * is unlimited, or set a finite limit above the parent limit.
     *
     * @throws ValidationException
     */
    public static function ensureWithinParentPlan(Plan $childPlan, ?Plan $parentPlan, string $field = 'plan_id'): void
    {
        if ($parentPlan === null) {
            throw self::validationException($field, 'The selected plan cannot be assigned without a parent plan.');
        }

        $parentResources = $parentPlan->resources()
            ->get()
            ->mapWithKeys(fn($resource) => [self::resourceIdentifier($resource->key, $resource->type) => (int)$resource->pivot->limit]);

        $childResources = $childPlan->resources()->get();

        foreach ($childResources as $childResource) {
            $childLimit = (int)$childResource->pivot->limit;

            if ($childLimit === 0) {
                continue;
            }

            $parentLimit = $parentResources->get(self::resourceIdentifier($childResource->key, $childResource->type));

            if ($parentLimit === null || $parentLimit === 0) {
                throw self::validationException($field, 'The selected plan grants resources that are not available in the parent plan.');
            }

            if ($childLimit === -1 && $parentLimit !== -1) {
                throw self::validationException($field, 'The selected plan grants unlimited resources above the parent plan.');
            }

            if ($childLimit > 0 && $parentLimit !== -1 && $childLimit > $parentLimit) {
                throw self::validationException($field, 'The selected plan grants resource limits above the parent plan.');
            }
        }
    }

    /**
     * Ensure a tenant-owned plan can be assigned to a direct child tenant.
     *
     * Tenant-owned plans are reusable templates. Quota is not reserved while the plan
     * exists; reservation happens only when the plan is assigned to a child tenant. The
     * plan must belong to the parent tenant, its resources must fit within
     * the parent's own plan, and those resources must fit into the parent's
     * currently available budget after real usage and existing child reservations are
     * subtracted.
     *
     * @throws ValidationException
     */
    public static function ensureAssignableToChildTenant(Plan $plan, Tenant $parentTenant, ?Tenant $childTenant = null, string $field = 'plan_id'): void
    {
        if ($plan->tenant_id !== $parentTenant->id) {
            throw self::validationException($field, 'The selected plan is not available for child tenants.');
        }

        self::ensureWithinParentPlan($plan->loadMissing('resources'), $parentTenant->plan, $field);
        self::ensureWithinAvailableTenantBudget($plan, $parentTenant, $childTenant, $field);
    }

    /**
     * Ensure a plan can be used inside the tenant context.
     *
     * @throws ValidationException
     */
    public static function ensurePlanAvailableForTenant(?string $planId, Tenant $tenant, string $field = 'plan_id'): void
    {
        if ($planId === null) {
            return;
        }

        $plan = Plan::query()->findOrFail($planId);

        if (!$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, trans('validation.exists', ['attribute' => $field]));
        }
    }

    /**
     * Ensure a plan can be assigned directly to an environment.
     *
     * Environment plans are budget contracts inside the tenant budget. They may be
     * global or tenant-owned, but all enabled resources must exist in and stay within
     * the tenant's own plan.
     *
     * @throws ValidationException
     */
    public static function ensureAssignableToEnvironment(?string $planId, Tenant $tenant, string $field = 'plan_id'): void
    {
        if ($planId === null) {
            return;
        }

        $plan = Plan::query()->with('resources')->findOrFail($planId);

        if (!$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, trans('validation.exists', ['attribute' => $field]));
        }

        self::ensureHasEnabledResources($plan, $field, 'The selected plan does not contain enabled resources.');
        self::ensureWithinParentPlan($plan, $tenant->plan, $field);
    }

    /**
     * Lock the tenant budget rows used by child-tenant reservation checks.
     *
     * Call this inside the same transaction that validates and writes reservations.
     */
    public static function lockTenantBudget(Tenant $tenant): void
    {
        Tenant::query()
            ->whereKey($tenant->id)
            ->lockForUpdate()
            ->first();

        TenantResourceReservation::query()
            ->where('tenant_id', $tenant->id)
            ->lockForUpdate()
            ->get();
    }

    /**
     * Lock rows that participate in validating a currently assigned plan mutation.
     */
    private static function lockPlanAssignments(Plan $plan): void
    {
        Plan::query()->whereKey($plan->id)->lockForUpdate()->first();

        DB::table('tenants')->where('plan_id', $plan->id)->lockForUpdate()->get();
        DB::table('environments')->where('plan_id', $plan->id)->lockForUpdate()->get();
        DB::table('tenant_user')->where('plan_id', $plan->id)->lockForUpdate()->get();
        DB::table('environment_user')->where('plan_id', $plan->id)->lockForUpdate()->get();
        DB::table('tenant_resource_reservations')->where('plan_id', $plan->id)->lockForUpdate()->get();

        foreach (self::tenantsAssignedToPlan($plan) as $tenant) {
            if ($tenant->parentTenant !== null) {
                self::lockTenantBudget($tenant->parentTenant);
            }
            self::lockTenantBudget($tenant);
        }
    }

    /**
     * Persist reservations for every enabled resource in the plan.
     *
     * Existing reservations for the same child tenant are replaced so plan changes and
     * tenant plan switches leave no stale budget assignments behind.
     */
    public static function syncTenantReservations(Tenant $parentTenant, Tenant $childTenant, Plan $plan): void
    {
        TenantResourceReservation::query()
            ->where('tenant_id', $parentTenant->id)
            ->where('reserved_for_tenant_id', $childTenant->id)
            ->delete();

        foreach (self::planLimits($plan) as $resourceLimit) {
            if ($resourceLimit['limit'] === 0) {
                continue;
            }

            TenantResourceReservation::query()->create([
                'tenant_id' => $parentTenant->id,
                'reserved_for_tenant_id' => $childTenant->id,
                'plan_id' => $plan->id,
                'resource_key' => $resourceLimit['key'],
                'resource_type' => $resourceLimit['type'],
                'limit' => $resourceLimit['limit'],
            ]);
        }
    }

    /**
     * Resynchronize reservations for every child tenant currently using this plan.
     */
    private static function syncReservationsForAssignedTenants(Plan $plan): void
    {
        foreach (self::tenantsAssignedToPlan($plan) as $tenant) {
            if ($plan->tenant_id === null || $tenant->parentTenant === null) {
                continue;
            }

            self::syncTenantReservations($tenant->parentTenant, $tenant, $plan);
        }
    }

    /**
     * Drop quota reservations held by the given parent for the child tenant.
     */
    public static function removeTenantReservations(Tenant $parentTenant, Tenant $childTenant): void
    {
        TenantResourceReservation::query()
            ->where('tenant_id', $parentTenant->id)
            ->where('reserved_for_tenant_id', $childTenant->id)
            ->delete();
    }

    /**
     * Return the currently available quota per resource for a tenant.
     *
     * Limit semantics are preserved: `-1` stays unlimited, `0` means unavailable, and
     * positive values are reduced by real usage and delegated child reservations.
     *
     * @return array<string, int>
     */
    public static function availableTenantBudget(Tenant $tenant, ?Tenant $ignoreChildTenant = null): array
    {
        $budget = [];

        foreach (self::planLimits($tenant->plan) as $identifier => $resourceLimit) {
            $limit = $resourceLimit['limit'];

            if ($limit === -1) {
                $budget[$identifier] = -1;
                continue;
            }

            $used = self::usageForTenant($tenant, $resourceLimit['key'], $resourceLimit['type']);

            $reserved = TenantResourceReservation::query()
                ->where('tenant_id', $tenant->id)
                ->where('resource_key', $resourceLimit['key'])
                ->where('resource_type', $resourceLimit['type'])
                ->when($ignoreChildTenant !== null, fn($query) => $query->where('reserved_for_tenant_id', '!=', $ignoreChildTenant->id))
                ->sum('limit');

            $budget[$identifier] = max(0, $limit - $used - (int)$reserved);
        }

        return $budget;
    }

    /**
     * Ensure the plan's enabled limits fit into the parent's free budget.
     *
     * @throws ValidationException
     */
    private static function ensureWithinAvailableTenantBudget(Plan $plan, Tenant $parentTenant, ?Tenant $childTenant, string $field): void
    {
        $available = self::availableTenantBudget($parentTenant, $childTenant);

        foreach (self::planLimits($plan) as $identifier => $resourceLimit) {
            $limit = $resourceLimit['limit'];

            if ($limit === 0) {
                continue;
            }

            $availableLimit = $available[$identifier] ?? 0;

            if ($availableLimit === -1) {
                continue;
            }

            if ($limit === -1 || $limit > $availableLimit) {
                throw self::validationException($field, 'The selected plan exceeds the parent tenant available resource budget.');
            }
        }
    }

    /**
     * Validate all existing assignments after a plan resource mutation.
     *
     * @throws ValidationException
     */
    private static function ensureAssignedPlanRemainsValid(Plan $plan): void
    {
        foreach (self::tenantsAssignedToPlan($plan) as $tenant) {
            if ($plan->tenant_id !== null && $tenant->parentTenant !== null) {
                self::ensureAssignableToChildTenant($plan, $tenant->parentTenant, $tenant, 'plan');
            }

            self::ensureTenantUsageWithinPlan($tenant, $plan, 'plan');
        }

        foreach (Environment::query()->where('plan_id', $plan->id)->with('tenant')->get() as $environment) {
            self::ensureWithinParentPlan($plan, $environment->tenant->plan, 'plan');
            self::ensureEnvironmentUsageWithinPlan($environment, $plan, 'plan');
        }

        foreach (DB::table('tenant_user')->where('plan_id', $plan->id)->get() as $assignment) {
            $tenant = Tenant::query()->findOrFail($assignment->tenant_id);
            self::ensureAssignableToTenantUser($plan->id, $tenant, 'plan');
            self::ensureTenantUserUsageWithinPlan($tenant, (string)$assignment->user_id, $plan, 'plan');
        }

        foreach (DB::table('environment_user')->where('plan_id', $plan->id)->get() as $assignment) {
            $environment = Environment::query()->with('tenant')->findOrFail($assignment->environment_id);
            self::ensureAssignableToEnvironmentUser($plan->id, $environment->tenant, $environment, 'plan');
            self::ensureEnvironmentUserUsageWithinPlan($environment, (string)$assignment->user_id, $plan, 'plan');
        }
    }

    /**
     * Return tenants currently assigned to the given plan.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tenant>
     */
    private static function tenantsAssignedToPlan(Plan $plan): \Illuminate\Database\Eloquent\Collection
    {
        return Tenant::query()
            ->where('plan_id', $plan->id)
            ->with('parentTenant')
            ->get();
    }

    /**
     * Ensure a tenant's current usage and delegated reservations fit into a plan.
     *
     * @throws ValidationException
     */
    private static function ensureTenantUsageWithinPlan(Tenant $tenant, Plan $plan, string $field): void
    {
        foreach (self::planLimits($plan) as $identifier => $resourceLimit) {
            $used = self::usageForTenant($tenant, $resourceLimit['key'], $resourceLimit['type'])
                + self::reservedByTenant($tenant, $resourceLimit['key'], $resourceLimit['type']);

            self::ensureLimitCoversUsage($resourceLimit['limit'], $used, $field);
        }

        self::ensureNoUsageOutsidePlan($plan, self::tenantUsageIdentifiers($tenant), $field);
    }

    /**
     * Ensure an environment's current usage fits into a plan.
     *
     * @throws ValidationException
     */
    private static function ensureEnvironmentUsageWithinPlan(Environment $environment, Plan $plan, string $field): void
    {
        foreach (self::planLimits($plan) as $resourceLimit) {
            if ($resourceLimit['type'] !== 'environment') {
                continue;
            }

            self::ensureLimitCoversUsage(
                $resourceLimit['limit'],
                self::usageForEnvironment($environment, $resourceLimit['key']),
                $field,
            );
        }

        self::ensureNoUsageOutsidePlan($plan, self::environmentUsageIdentifiers($environment), $field);
    }

    /**
     * Ensure a tenant user's current usage fits into its explicit plan.
     *
     * @throws ValidationException
     */
    private static function ensureTenantUserUsageWithinPlan(Tenant $tenant, string $userId, Plan $plan, string $field): void
    {
        foreach (self::planLimits($plan) as $resourceLimit) {
            if ($resourceLimit['type'] !== 'tenant') {
                continue;
            }

            self::ensureLimitCoversUsage(
                $resourceLimit['limit'],
                self::usageForTenant($tenant, $resourceLimit['key'], 'tenant', $userId),
                $field,
            );
        }
    }

    /**
     * Ensure an environment user's current usage fits into its explicit plan.
     *
     * @throws ValidationException
     */
    private static function ensureEnvironmentUserUsageWithinPlan(Environment $environment, string $userId, Plan $plan, string $field): void
    {
        foreach (self::planLimits($plan) as $resourceLimit) {
            if ($resourceLimit['type'] !== 'environment') {
                continue;
            }

            self::ensureLimitCoversUsage(
                $resourceLimit['limit'],
                self::usageForEnvironment($environment, $resourceLimit['key'], $userId),
                $field,
            );
        }
    }

    /**
     * Reject a plan mutation when current usage would exceed the new limit.
     *
     * @throws ValidationException
     */
    private static function ensureLimitCoversUsage(int $limit, int $used, string $field): void
    {
        if ($limit === -1 || $used === 0) {
            return;
        }

        if ($limit <= 0 || $used > $limit) {
            throw self::validationException($field, 'The plan is already used above the requested resource limit.');
        }
    }

    /**
     * Reject removing resources that still have usage records.
     *
     * @param array<int, string> $usageIdentifiers
     * @throws ValidationException
     */
    private static function ensureNoUsageOutsidePlan(Plan $plan, array $usageIdentifiers, string $field): void
    {
        $planIdentifiers = array_keys(self::planLimits($plan));

        foreach ($usageIdentifiers as $usageIdentifier) {
            if (!in_array($usageIdentifier, $planIdentifiers, true)) {
                throw self::validationException($field, 'The plan resource is already in use and cannot be removed.');
            }
        }
    }

    private static function reservedByTenant(Tenant $tenant, string $resourceKey, string $resourceType): int
    {
        return (int)TenantResourceReservation::query()
            ->where('tenant_id', $tenant->id)
            ->where('resource_key', $resourceKey)
            ->where('resource_type', $resourceType)
            ->sum('limit');
    }

    /**
     * Return resource limits keyed by resource type and key for the given plan.
     *
     * @return array<string, array{key: string, type: string, limit: int}>
     */
    private static function planLimits(Plan $plan): array
    {
        return $plan->resources()
            ->get()
            ->mapWithKeys(fn($resource) => [
                self::resourceIdentifier($resource->key, $resource->type) => [
                    'key' => $resource->key,
                    'type' => $resource->type,
                    'limit' => (int)$resource->pivot->limit,
                ],
            ])
            ->all();
    }

    /**
     * Ensure that an explicitly assigned plan contains enabled resources.
     *
     * Plans without enabled limits are valid as templates, but assigning them as an
     * explicit user limit plan would grant no usable budget.
     *
     * @throws ValidationException
     */
    private static function ensureHasEnabledResources(Plan $plan, string $field, string $message): void
    {
        $hasEnabledResource = $plan->resources()
            ->wherePivot('limit', '!=', 0)
            ->exists();

        if (!$hasEnabledResource) {
            throw self::validationException($field, $message);
        }
    }

    private static function resourceIdentifier(string $key, string $type): string
    {
        return $type . ':' . $key;
    }

    private static function usageForTenant(Tenant $tenant, string $resourceKey, string $resourceType, ?string $userId = null): int
    {
        if ($resourceType === 'environment') {
            return (int)DB::table('env_usage')
                ->join('environments', 'env_usage.environment_id', '=', 'environments.id')
                ->where('environments.tenant_id', $tenant->id)
                ->where('env_usage.resource_key', $resourceKey)
                ->when($userId !== null, fn($query) => $query->where('env_usage.user_id', $userId))
                ->count();
        }

        return (int)DB::table('tenant_usage')
            ->where('tenant_id', $tenant->id)
            ->where('resource_key', $resourceKey)
            ->when($userId !== null, fn($query) => $query->where('user_id', $userId))
            ->count();
    }

    private static function usageForEnvironment(Environment $environment, string $resourceKey, ?string $userId = null): int
    {
        return (int)DB::table('env_usage')
            ->where('environment_id', $environment->id)
            ->where('resource_key', $resourceKey)
            ->when($userId !== null, fn($query) => $query->where('user_id', $userId))
            ->count();
    }

    /**
     * @return array<int, string>
     */
    private static function tenantUsageIdentifiers(Tenant $tenant): array
    {
        $tenantUsage = DB::table('tenant_usage')
            ->where('tenant_id', $tenant->id)
            ->select('resource_key')
            ->distinct()
            ->pluck('resource_key')
            ->map(fn(string $key) => self::resourceIdentifier($key, 'tenant'));

        $environmentUsage = DB::table('env_usage')
            ->join('environments', 'env_usage.environment_id', '=', 'environments.id')
            ->where('environments.tenant_id', $tenant->id)
            ->select('env_usage.resource_key')
            ->distinct()
            ->pluck('resource_key')
            ->map(fn(string $key) => self::resourceIdentifier($key, 'environment'));

        $reserved = TenantResourceReservation::query()
            ->where('tenant_id', $tenant->id)
            ->select('resource_key', 'resource_type')
            ->distinct()
            ->get()
            ->map(fn(TenantResourceReservation $reservation) => self::resourceIdentifier($reservation->resource_key, $reservation->resource_type));

        return $tenantUsage
            ->merge($environmentUsage)
            ->merge($reserved)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function environmentUsageIdentifiers(Environment $environment): array
    {
        return DB::table('env_usage')
            ->where('environment_id', $environment->id)
            ->select('resource_key')
            ->distinct()
            ->pluck('resource_key')
            ->map(fn(string $key) => self::resourceIdentifier($key, 'environment'))
            ->values()
            ->all();
    }

    private static function environmentParentPlan(Environment $environment): ?Plan
    {
        return $environment->plan ?: $environment->tenant->plan;
    }

    private static function validationException(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
