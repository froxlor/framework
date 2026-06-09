<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $permission_id
 * @property string $role_id
 * @property boolean $inheritable
 * @property Permission $permission
 * @property Role $role
 */
class PermissionRole extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
