<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Observers\NodeObserver;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Platform\NodePlatform;
use Froxlor\Core\Services\Node\Platform\PlatformResolver;
use Froxlor\Core\Services\Node\Traits\HasAdapter;
use Froxlor\Core\Services\Traits\HasSettings;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $adapter
 * @property string $name
 * @property string|null $description
 * @property string $hostname
 * @property string $username
 * @property string|null $password
 * @property boolean $sudo
 * @property array $properties
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection<NodeEnvironment> $environments
 * @property Collection<NodeInterface> $nodeInterfaces
 * @property string latestUnixName
 * @property int $latestGuid
 */
#[ObservedBy(NodeObserver::class)]
class Node extends Model
{
    use HasUlids, HasAdapter, HasSettings;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'sshkey',
    ];

    protected $casts = [
        'sudo' => 'boolean',
        'password' => 'encrypted',
        'properties' => 'encrypted:array',
    ];

    protected $appends = [
        'environments_count',
        'tenants_count'
    ];

    protected $with = [
        'nodeInterfaces'
    ];

    public function environments(): BelongsToMany
    {
        return $this->belongsToMany(
            Environment::class,
            'node_environments',
            'node_id',
            'environment_id'
        )->withPivot(['unix_name', 'guid'])->using(NodeEnvironment::class);
    }

    public function nodeInterfaces(): HasMany
    {
        return $this->hasMany(NodeInterface::class);
    }

    /**
     * returns the number of hosted environments on that node
     *
     * @return Attribute
     */
    public function environmentsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->environments()->count()
        );
    }

    /**
     * returns the latest unix-name for this node
     *
     * @return Attribute
     */
    public function latestUnixName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getSetting('node.username_prefix') . ($this->getSetting('node.last_username_number') + 1),
        );
    }

    /**
     * returns the latest guid for this node
     *
     * @return Attribute
     */
    public function latestGuid(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getSetting('node.last_guid_number') + 1,
        );
    }

    /**
     * returns the number of tenants (unique) on that node that have one or more environments deployed there
     *
     * @return Attribute
     */
    public function tenantsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->environments()->pluck('tenant_id')->unique()->count(),
        );
    }

    public function platform(): NodePlatform
    {
        return app(PlatformResolver::class)->fromNode($this);
    }
}
