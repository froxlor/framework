<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasSettings;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $node_id
 * @property string $bind_addr
 * @property string $nat_addr
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Node $node
 */
class NodeInterface extends Model
{
    use HasUlids, HasSettings;

    protected $guarded = [];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
