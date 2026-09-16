<?php

namespace Froxlor\Core\Services\Traits;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Resource;

/** Book tenant-owned metadata resources; global/bootstrap records have no tenant charge. */
trait TracksTenantQuota
{
    use SavesWithinQuotaTransaction;

    public static function bootTracksTenantQuota(): void
    {
        static::created(function ($model): void {
            if ($model->tenant_id !== null && auth()->check()) {
                Resource::addUsage(Tenant::query()->whereKey($model->tenant_id)->lockForUpdate()->firstOrFail(), $model, auth()->user());
            }
        });
        static::deleted(function ($model): void {
            if ($model->tenant_id !== null && ($tenant = Tenant::query()->find($model->tenant_id))) {
                Resource::removeUsage($tenant, $model);
            }
        });
    }
}
