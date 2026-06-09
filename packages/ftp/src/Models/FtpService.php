<?php

namespace Froxlor\Ftp\Models;

use Froxlor\Core\Models\Node;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $node_id
 * @property string $name
 * @property string $driver
 * @property string $listen_address
 * @property int $port
 * @property bool $allow_local_users
 * @property bool $allow_write
 * @property bool $chroot_local_users
 * @property bool $allow_writable_chroot
 * @property int $passive_min_port
 * @property int $passive_max_port
 * @property string $status
 * @property Carbon|null $installed_at
 * @property Carbon|null $configured_at
 * @property Carbon|null $last_checked_at
 * @property bool $is_reachable
 * @property string|null $last_error
 * @property array|null $properties
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property Node $node
 */
class FtpService extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'allow_local_users' => 'boolean',
        'allow_write' => 'boolean',
        'chroot_local_users' => 'boolean',
        'allow_writable_chroot' => 'boolean',
        'installed_at' => 'datetime',
        'configured_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'is_reachable' => 'boolean',
        'properties' => 'encrypted:array',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
