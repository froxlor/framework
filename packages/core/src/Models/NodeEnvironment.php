<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Observers\NodeEnvironmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $node_id
 * @property string $environment_id
 * @property string $unix_name
 * @property string $guid
 * @property string $mode
 * @property string|null $jail_path
 * @property array|null $jail_manifest Last successfully applied package declarations (not ownership authority).
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Node $node
 * @property Environment $environment
 */
#[ObservedBy(NodeEnvironmentObserver::class)]
class NodeEnvironment extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    protected function casts(): array
    {
        return ['guid' => 'integer', 'jail_manifest' => 'array'];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }
}
