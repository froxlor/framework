<?php

namespace Froxlor\Web\Models;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\NodeInterface;
use Froxlor\Domain\Models\Domain;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $domain_id
 * @property string $node_id
 * @property string $documentroot
 * @property bool $access_log
 * @property bool $error_log
 * @property string $alias_mode
 * @property bool $notryfiles
 * @property string $custom_vhost
 * @property string $custom_ssl_vhost
 * @property bool $include_custom_vhost_in_ssl
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Domain $domain
 * @property Node $node
 * @property Collection<DomainVhostsNodeInterfaces> $nodeInterfaces
 * @property DomainSslVhost $domainSslVhost
 */
class DomainVhost extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function nodeInterfaces(): BelongsToMany
    {
        return $this->belongsToMany(
            NodeInterface::class,
            'domain_vhosts_node_interfaces',
            'domain_vhost_id',
            'node_interface_id'
        )->withPivot(['port', 'ssl_port'])->using(DomainVhostsNodeInterfaces::class);
    }

    public function domainSslVhost(): hasOne
    {
        return $this->hasOne(DomainSslVhost::class);
    }
}
