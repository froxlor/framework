<?php

namespace Froxlor\Domain\Models;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $parent_domain_id
 * @property string $tenant_id
 * @property string|null $environment_id
 * @property string|null $node_id
 * @property string $domain
 * @property array $properties
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Environment|null $environment
 * @property Node|null $node
 * @property Tenant $tenant
 */
class Domain extends Model
{
    use HasUlids, HasPermissions, IsResource, IsEnvironmentResource;

    protected $guarded = [];

    protected $casts = [
        'properties' => 'array'
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
