<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property MorphTo $auditable
 * @property string $tenant_id
 * @property string $environment_id
 * @property string $action
 * @property array $context
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Tenant|null $tenant
 * @property Environment|null $environment
 */
class AuditLog extends Model
{
    use HasUlids, HasPermissions, IsResource;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array'
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public static function getAllPermissions(): array
    {
        return [
            ['key' => 'audit-log.*', 'name' => 'Manage audit log'],
            ['key' => 'audit-log.index', 'name' => 'View audit log'],
            ['key' => 'tenants.audit-log.*', 'name' => 'Manage tenant audit log'],
            ['key' => 'tenants.audit-log.index', 'name' => 'View tenant audit log'],
            ['key' => 'tenants.environments.audit-log.*', 'name' => 'Manage environment audit log'],
            ['key' => 'tenants.environments.audit-log.index', 'name' => 'View environment audit log'],
        ];
    }
}
