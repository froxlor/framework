<?php

namespace Froxlor\Database\Models;

use Froxlor\Core\Models\Node;
use Froxlor\Database\Observers\DatabaseServerObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $node_id
 * @property string $name
 * @property string $driver
 * @property string $host
 * @property int $port
 * @property string|null $admin_username
 * @property string|null $admin_password
 * @property bool $supports_per_environment_users
 * @property int|null $max_databases
 * @property string $status
 * @property array|null $properties
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Node $node
 * @property Collection<Database> $databases
 */
#[ObservedBy(DatabaseServerObserver::class)]
class DatabaseServer extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'admin_password',
    ];

    protected $casts = [
        'admin_password' => 'encrypted',
        'supports_per_environment_users' => 'boolean',
        'installed_at' => 'datetime',
        'configured_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'is_reachable' => 'boolean',
        'properties' => 'encrypted:array',
    ];

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
