<?php

namespace Froxlor\Web\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $domain_vhost_id
 * @property boolean $ssl_redirect
 * @property string $ssl_mode // ['off', 'auto', 'manual']
 * @property boolean $http2
 * @property boolean $http3
 * @property boolean $hsts_enabled
 * @property integer $hsts_mode
 * @property int $hsts_maxage
 * @property boolean $oscp_stapling
 * @property boolean $override_tls
 * @property string $ssl_protocols
 * @property string $ssl_cipher_list
 * @property string $tlsv13_cipher_list
 * @property boolean $ssl_honorcipherorder
 * @property boolean $ssl_sessiontickets
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property DomainVhost $domainVhost
 */
class DomainSslVhost extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    public function domainVhost(): BelongsTo
    {
        return $this->belongsTo(DomainVhost::class);
    }

}
