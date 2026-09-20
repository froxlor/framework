<?php

namespace Froxlor\Core\Support;

use Closure;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Shared serialization boundary for usage, reservations and plan/assignment mutations. */
final class Quota
{
    public static function transaction(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            self::lock();

            return $operation();
        });
    }

    /** Always acquire before budget reads; never hold this lock across infrastructure work. */
    public static function lock(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Quota mutations require a database transaction.');
        }
        if (DB::table('quota_locks')->where('id', 1)->lockForUpdate()->first() === null) {
            throw new LogicException('Quota lock is missing. Apply the Core quota migration.');
        }
    }

    public static function tenantAvailable(Tenant $tenant, string $key, User $user): bool
    {
        return self::transaction(function () use ($tenant, $key, $user): bool {
            $tenant = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $membership = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->lockForUpdate()->first();
            if ($membership === null && Resource::actingTenantFor($user, $tenant) === null) {
                throw new UnknownTenantUserException('Unknown tenant user');
            }

            return self::tenantTotalAvailable($tenant, $key, 'tenant')
                && self::allows(self::limit($membership?->plan_id ?? $tenant->plan_id, $key, 'tenant'),
                    self::tenantUsed($tenant->id, $key, 'tenant', $user->id));
        });
    }

    public static function environmentAvailable(Environment $environment, string $key, User $user): bool
    {
        return self::transaction(function () use ($environment, $key, $user): bool {
            $environment = Environment::query()->whereKey($environment->id)->lockForUpdate()->firstOrFail();
            $tenant = Tenant::query()->whereKey($environment->tenant_id)->lockForUpdate()->firstOrFail();
            $membership = DB::table('environment_user')->where('environment_id', $environment->id)->where('user_id', $user->id)->lockForUpdate()->first();
            if ($membership === null && Resource::actingTenantFor($user, $tenant) === null) {
                throw new UnknownEnvironmentUserException('Unknown environment user');
            }
            $tenantMembership = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->lockForUpdate()->first();
            $environmentPlan = $environment->plan_id ?? $tenant->plan_id;

            return self::tenantTotalAvailable($tenant, $key, 'environment')
                && self::allows(self::limit($environmentPlan, $key, 'environment'), self::environmentUsed($environment->id, $key))
                && self::allows(self::limit($membership?->plan_id ?? $environmentPlan, $key, 'environment'), self::environmentUsed($environment->id, $key, $user->id))
                && ($tenantMembership?->plan_id === null || self::allows(
                    self::limit($tenantMembership->plan_id, $key, 'environment'), self::tenantUsed($tenant->id, $key, 'environment', $user->id)));
        });
    }

    private static function tenantTotalAvailable(Tenant $tenant, string $key, string $type): bool
    {
        $limit = self::limit($tenant->plan_id, $key, $type);
        if ($limit === -1) {
            return true;
        }
        $reservations = DB::table('tenant_resource_reservations')->where('tenant_id', $tenant->id)
            ->where('resource_key', $key)->where('resource_type', $type)->lockForUpdate()->pluck('limit');
        if ($reservations->contains(fn ($value) => (int) $value === -1)) {
            return false;
        }

        return self::allows($limit, self::tenantUsed($tenant->id, $key, $type) + (int) $reservations->sum());
    }

    private static function allows(int $limit, int $used): bool
    {
        return $limit === -1 || ($limit > 0 && $used < $limit);
    }

    public static function limit(?string $planId, string $key, string $type): int
    {
        if ($planId === null) {
            return 0;
        }

        return (int) (DB::table('plan_resource')->join('resources', 'resources.id', '=', 'plan_resource.resource_id')
            ->where('plan_resource.plan_id', $planId)->where('resources.key', $key)->where('resources.type', $type)
            ->lockForUpdate()->value('plan_resource.limit') ?? 0);
    }

    public static function tenantUsed(string $tenantId, string $key, string $type, ?string $userId = null): int
    {
        $query = $type === 'environment'
            ? DB::table('env_usage')->join('environments', 'environments.id', '=', 'env_usage.environment_id')->where('environments.tenant_id', $tenantId)
            : DB::table('tenant_usage')->where('tenant_id', $tenantId);
        $table = $type === 'environment' ? 'env_usage' : 'tenant_usage';
        $columns = $type === 'environment' ? ['env_usage.environment_id', 'env_usage.resource_id'] : ['tenant_usage.resource_id'];

        return $query->where($table.'.resource_key', $key)
            ->when($userId !== null, fn ($query) => $query->where($table.'.user_id', $userId))
            ->lockForUpdate()->get($columns)->unique(fn ($row) => ($row->environment_id ?? '').':'.$row->resource_id)->count();
    }

    public static function environmentUsed(string $environmentId, string $key, ?string $userId = null): int
    {
        return DB::table('env_usage')->where('environment_id', $environmentId)->where('resource_key', $key)
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->lockForUpdate()->get(['resource_id'])->pluck('resource_id')->unique()->count();
    }
}
