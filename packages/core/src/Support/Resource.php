<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\ResourceNotFoundException;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Resource
{

    /**
     * @param Tenant $tenant
     * @param string|Model $resource resource to check
     * @param User|null $user optional to check usage of a specific users in the environment
     * @return int
     * @throws InvalidResourceException
     */
    public static function getUsage(Tenant $tenant, string|Model $resource, ?User $user = null): int
    {
        if (!is_string($resource) && !in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        $usage = $tenant->tenantUsages()
            ->where('resource_key', '=', self::resourceKey($resource));
        if ($user) {
            $usage->where('user_id', $user->id);
        }
        return $usage->count();
    }

    /**
     * @param Environment $environment
     * @param string|Model $resource resource to check
     * @param User|null $user optional to check usage of a specific users in the environment
     * @return int
     * @throws InvalidResourceException
     */
    public static function getEnvironmentUsage(Environment $environment, string|Model $resource, ?User $user = null): int
    {
        if (!is_string($resource) && !in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        $usage = $environment->envUsages()
            ->where('resource_key', '=', self::resourceKey($resource));
        if ($user) {
            $usage->where('user_id', $user->id);
        }
        return $usage->count();
    }

    /**
     * @param Tenant $tenant
     * @param Model $resource used resource
     * @param User|null $user optional set origin user or use currently logged-in user
     * @return Model
     * @throws ResourceLimitException
     * @throws UnknownEnvironmentUserException
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     * @throws UnknownTenantUserException
     */
    public static function addUsage(Tenant $tenant, Model $resource, ?User $user = null): Model
    {
        if (!in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        if (empty($user)) {
            $user = auth()->user();
        }
        if (self::hasUsageAvailable($tenant, $resource, $user)) {
            return $tenant->tenantUsages()->create([
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'resource_key' => $resource::getResourceKey(),
                'resource_id' => $resource->id
            ]);
        }
        throw new ResourceLimitException("Resource limit exceeded (" . $resource::getResourceKey() . ")");
    }

    /**
     * @throws InvalidResourceException
     * @throws UnknownTenantUserException
     */
    public static function hasUsageAvailable(Tenant $tenant, string|Model $resource, User $user): bool
    {
        if (!is_string($resource) && !in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }

        try {
            return $tenant->userHasResourceAvailable($user, self::resourceKey($resource));
        } catch (UnknownTenantUserException $exception) {
            if (!self::userControlsTenant($user, $tenant)) {
                throw $exception;
            }

            return self::tenantPlanHasResourceAvailable($tenant, $resource);
        }
    }

    /**
     * @param Environment $environment
     * @param Model $resource used resource
     * @param User|null $user optional set origin user or use currently logged-in user
     * @return Model
     * @throws ResourceLimitException
     * @throws UnknownEnvironmentUserException
     * @throws InvalidResourceException
     */
    public static function addEnvironmentUsage(Environment $environment, Model $resource, ?User $user = null): Model
    {
        if (!in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        if (empty($user)) {
            $user = auth()->user();
        }
        if ($environment->userHasResourceAvailable($user, $resource::getResourceKey())) {
            return $environment->envUsages()->create([
                'user_id' => !empty($user) ? $user->id : auth()->user()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'resource_key' => $resource::getResourceKey(),
                'resource_id' => $resource->id
            ]);
        }
        throw new ResourceLimitException("Resource limit exceeded (" . $resource::getResourceKey() . ")");
    }

    /**
     * @param Tenant $tenant
     * @param Model $resource resource to remove
     * @return bool
     * @throws InvalidResourceException
     */
    public static function removeUsage(Tenant $tenant, Model $resource): bool
    {
        if (!in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        $tenant->tenantUsages()
            ->where('resource_key', '=', $resource::getResourceKey())
            ->where('resource_id', $resource->id)
            ->delete();
        return true;
    }

    /**
     * @param Environment $environment
     * @param Model $resource resource to remove
     * @return bool
     * @throws InvalidResourceException
     */
    public static function removeEnvironmentUsage(Environment $environment, Model $resource): bool
    {
        if (!in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException("Given resource does not implement " . IsResource::class);
        }
        $envUsage = $environment->envUsages()
            ->where('resource_key', '=', $resource::getResourceKey())
            ->where('resource_id', $resource->id)
            ->first();
        $envUsage->delete();
        return true;
    }

    public static function resourceKey(string|Model $resource): string
    {
        if (is_string($resource) && class_exists($resource) && in_array(IsResource::class, class_uses_recursive($resource))) {
            return $resource::getResourceKey();
        }

        return is_string($resource) ? $resource : $resource::getResourceKey();
    }

    /**
     * Resolve the tenant context a user acts from when consuming resources for a target tenant.
     *
     * Returns the target tenant if the user belongs to it directly, otherwise the first user tenant that owns the
     * target tenant as a subtenant. Returns null when the user cannot act for the target tenant.
     */
    public static function actingTenantFor(User $user, Tenant $targetTenant): ?Tenant
    {
        /** @var \Illuminate\Support\Collection<int, Tenant> $tenants */
        $tenants = $user->tenants()->get();

        /** @var Tenant|null $directTenant */
        $directTenant = $tenants->firstWhere('id', $targetTenant->id);
        if ($directTenant !== null) {
            return $directTenant;
        }

        /** @var Tenant|null $parentTenant */
        $parentTenant = $tenants->first(
            fn(Tenant $tenant) => in_array($targetTenant->id, $tenant->descendantIds(), true)
        );

        return $parentTenant;
    }

    private static function tenantPlanHasResourceAvailable(Tenant $tenant, string|Model $resource): bool
    {
        $resourceToCheck = $tenant->plan?->resources()
            ->where('resources.key', self::resourceKey($resource))
            ->where('resources.type', 'tenant')
            ->first();

        if (empty($resourceToCheck)) {
            return false;
        }

        $max = $resourceToCheck->pivot->limit;
        if (empty($max)) {
            return false;
        }

        if ($max == -1) {
            return true;
        }

        return self::getUsage($tenant, $resourceToCheck->model_type) < $max;
    }

    private static function userControlsTenant(User $user, Tenant $targetTenant): bool
    {
        return $user->tenants()
            ->get()
            ->contains(fn(Tenant $tenant) => in_array($targetTenant->id, $tenant->descendantIds(), true));
    }
}
