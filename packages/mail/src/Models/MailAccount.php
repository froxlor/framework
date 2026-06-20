<?php

namespace Froxlor\Mail\Models;

use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $mail_address_id
 * @property string $username
 * @property string $password
 * @property int $uid
 * @property int $gid
 * @property string $homedir
 * @property string $maildir
 * @property bool $smtp_enabled
 * @property bool $pop3_enabled
 * @property bool $imap_enabled
 * @property int $quota
 * @property int $mboxsize
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property MailAddress $mail_address
 */
class MailAccount extends Model
{
    use HasUlids, SoftDeletes, IsResource, IsEnvironmentResource;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function mailAddress(): BelongsTo
    {
        return $this->belongsTo(MailAddress::class);
    }
}
