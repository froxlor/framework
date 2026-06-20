<?php

namespace Froxlor\Database\Models;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Database\Observers\DatabaseObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $environment_id
 * @property string|null $database_server_id
 * @property string $name
 * @property string|null $database_name
 * @property string|null $username
 * @property string|null $password
 * @property string $engine
 * @property string $charset
 * @property string $collation
 * @property string $status
 * @property string|null $last_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Environment $environment
 * @property DatabaseServer|null $databaseServer
 */
#[ObservedBy(DatabaseObserver::class)]
class Database extends Model
{
    use HasUlids, IsResource, IsEnvironmentResource, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'password' => 'encrypted',
        'provisioned_at' => 'datetime',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function databaseServer(): BelongsTo
    {
        return $this->belongsTo(DatabaseServer::class);
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => str($this->status)->headline()->value(),
        );
    }
}
