<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reserved quota delegated from a tenant to one direct child tenant.
 *
 * Actual usage remains in the scope-specific usage tables; reservations represent
 * budget that is no longer available to the parent because it has been assigned to a
 * child tenant.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $reserved_for_tenant_id
 * @property string $plan_id
 * @property string $resource_key
 * @property string $resource_type
 * @property int $limit
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Tenant $tenant
 * @property Tenant $reservedForTenant
 * @property Plan $plan
 */
class TenantResourceReservation extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reservedForTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'reserved_for_tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
