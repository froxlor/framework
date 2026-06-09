<?php

namespace Froxlor\Web\Models;

use Froxlor\Core\Models\NodeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $domain_vhost_id
 * @property string $node_interface_id
 * @property int $port
 * @property bool $ssl_port
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property DomainVhost $domainVhost
 * @property NodeInterface $nodeInterface
 */
class DomainVhostsNodeInterfaces extends Pivot
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    public function domainVhost(): BelongsTo
    {
        return $this->belongsTo(DomainVhost::class);
    }

    public function nodeInterface(): BelongsTo
    {
        return $this->belongsTo(NodeInterface::class);
    }
}
