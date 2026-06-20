<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanAssignments
{
    /**
     * Ensure that a plan can be assigned as an optional tenant-user limit plan.
     *
     * Tenant users inherit the tenant plan when no explicit plan is assigned. When an
     * explicit plan is assigned, it must be a tenant-scope plan available in the tenant
     * context and it must not grant more resources than the tenant's own plan.
     *
     * @throws ValidationException
     */
    public static function ensureAssignableToTenantUser(?string $planId, Tenant $tenant, string $field = 'plan_id'): void
    {
        if (empty($planId)) {
            return;
        }

        $plan = Plan::query()->with('resources')->findOrFail($planId);

        if (!$plan->isTenantPlan() || !$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, 'The selected plan is not available for this tenant.');
        }

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

        if (!$plan->isEnvironmentPlan() || !$plan->isAvailableForTenant($tenant)) {
            throw self::validationException($field, 'The selected plan is not available for this environment.');
        }

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
     * Plan resources must match the plan scope. For tenant-owned tenant plans, the
     * resource limit must also stay within the owning tenant's assigned plan so tenants
     * cannot create child plans that grant more tenant-scope capacity than they own.
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
        if ($resource->type !== $plan->type) {
            throw self::validationException($field, 'The selected resource is not available for this plan type.');
        }

        if ($tenant === null || !$plan->isTenantPlan() || $limit === 0) {
            return;
        }

        $parentPlan = $tenant->plan;
        if ($parentPlan === null) {
            throw self::validationException('limit', 'The resource cannot be assigned without a parent plan.');
        }

        $parentResource = $parentPlan->resources()
            ->where('resources.key', $resource->key)
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
            ->mapWithKeys(fn($resource) => [$resource->key => (int)$resource->pivot->limit]);

        foreach ($childPlan->resources as $childResource) {
            $childLimit = (int)$childResource->pivot->limit;

            if ($childLimit === 0) {
                continue;
            }

            $parentLimit = $parentResources->get($childResource->key);

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

    private static function validationException(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
