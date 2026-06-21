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
        self::ensureWithinParentPlan($plan, $environment->plan, $field);
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

    private static function usageForTenant(Tenant $tenant, string $resourceKey, string $resourceType): int
    {
        if ($resourceType === 'environment') {
            return DB::table('env_usage')
                ->join('environments', 'env_usage.environment_id', '=', 'environments.id')
                ->where('environments.tenant_id', $tenant->id)
                ->where('env_usage.resource_key', $resourceKey)
                ->count();
        }

        return DB::table('tenant_usage')
            ->where('tenant_id', $tenant->id)
            ->where('resource_key', $resourceKey)
            ->count();
    }

    private static function validationException(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
