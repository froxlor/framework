<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Model;

/** Quota facade: availability is advisory; booking always rechecks under the shared lock. */
class Resource
{
    public static function getUsage(Tenant $tenant, string|Model $resource, ?User $user = null): int
    {
        return Quota::tenantUsed($tenant->id, self::resourceKey($resource), 'tenant', $user?->id);
    }

    public static function getEnvironmentUsage(Environment $environment, string|Model $resource, ?User $user = null): int
    {
        return Quota::environmentUsed($environment->id, self::resourceKey($resource), $user?->id);
    }

    /** Use this boundary around resource creation AND booking in external packages. */
    public static function transaction(\Closure $operation): mixed
    {
        return Quota::transaction($operation);
    }

    /** Idempotent per owning tenant/resource, retaining the original consuming user. */
    public static function addUsage(Tenant $tenant, Model $resource, ?User $user = null): Model
    {
        return Quota::transaction(function () use ($tenant, $resource, $user): Model {
            $key = self::resourceKey($resource);
            $user ??= auth()->user();
            if (!$resource->exists || $user === null) {
                throw new InvalidResourceException('Usage requires a persisted resource and an explicit user.');
            }
            $existing = $tenant->tenantUsages()->where('resource_key', $key)->where('resource_id', $resource->id)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing;
            }
            if (!self::hasUsageAvailable($tenant, $resource, $user)) {
                throw new ResourceLimitException('Resource limit exceeded ('.$key.')');
            }
            return $tenant->tenantUsages()->create([
                'user_id' => $user->id, 'resource_key' => $key, 'resource_id' => $resource->id,
            ]);
        });
    }

    public static function hasUsageAvailable(Tenant $tenant, string|Model $resource, User $user): bool
    {
        return Quota::tenantAvailable($tenant, self::resourceKey($resource), $user);
    }

    /** Environment usage is aggregated for the owner tenant, never recursively duplicated. */
    public static function addEnvironmentUsage(Environment $environment, Model $resource, ?User $user = null): Model
    {
        return Quota::transaction(function () use ($environment, $resource, $user): Model {
            $key = self::resourceKey($resource);
            $user ??= auth()->user();
            if (!$resource->exists || $user === null) {
                throw new InvalidResourceException('Usage requires a persisted resource and an explicit user.');
            }
            $existing = $environment->envUsages()->where('resource_key', $key)->where('resource_id', $resource->id)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing;
            }
            if (!Quota::environmentAvailable($environment, $key, $user)) {
                throw new ResourceLimitException('Resource limit exceeded ('.$key.')');
            }
            return $environment->envUsages()->create([
                'user_id' => $user->id, 'resource_key' => $key, 'resource_id' => $resource->id,
            ]);
        });
    }

    public static function removeUsage(Tenant $tenant, Model $resource): bool
    {
        return Quota::transaction(function () use ($tenant, $resource): bool {
            $tenant->tenantUsages()->where('resource_key', self::resourceKey($resource))->where('resource_id', $resource->id)->delete();
            return true;
        });
    }

    public static function removeEnvironmentUsage(Environment $environment, Model $resource): bool
    {
        return Quota::transaction(function () use ($environment, $resource): bool {
            $environment->envUsages()->where('resource_key', self::resourceKey($resource))->where('resource_id', $resource->id)->delete();
            return true;
        });
    }

    public static function resourceKey(string|Model $resource): string
    {
        if (is_string($resource) && !class_exists($resource)) {
            return $resource;
        }
        if (!in_array(IsResource::class, class_uses_recursive($resource))) {
            throw new InvalidResourceException('Given resource does not implement '.IsResource::class);
        }
        return $resource::getResourceKey();
    }

    /** Customer ownership, not permission authorization. Callers must still use policies. */
    public static function actingTenantFor(User $user, Tenant $targetTenant): ?Tenant
    {
        $tenants = $user->tenants()->lockForUpdate()->get();
        return $tenants->firstWhere('id', $targetTenant->id)
            ?? $tenants->first(fn (Tenant $tenant) => in_array($targetTenant->id, $tenant->descendantIds(), true));
    }
}
