<?php

namespace Froxlor\Mail\Models;

use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Domain\Models\Domain;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $domain_id
 * @property string $address
 * @property string $destination
 * @property string $description
 * @property bool $is_catchall
 * @property float $spam_tag_level
 * @property bool $rewrite_subject
 * @property float $spam_kill_level
 * @property bool $bypass_spam
 * @property bool $policy_greylist
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Domain $domain
 */
class MailAddress extends Model
{
    use HasUlids, SoftDeletes, IsResource, IsEnvironmentResource;

    protected $guarded = [];

    protected $appends = [
        'mailAccount'
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function mailAccount(): hasOne
    {
        return $this->hasOne(MailAccount::class);
    }
}
