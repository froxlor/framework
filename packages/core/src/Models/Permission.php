<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection<PermissionRole> $roles
 */
class Permission extends Model
{
    use HasUlids, HasPermissions, IsResource;

    protected $guarded = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('inheritable')->using(PermissionRole::class);
    }

    /**
     * generate all parent-wildcard permission strings from given permissions
     * @param string|array $permission
     * @param bool $strict
     * @return array
     */
    public static function generatePermissionPath(string|array $permission, bool $strict = false): array
    {
        if ($strict) {
            return (array)$permission;
        }
        $permission = (array)$permission;
        $result = [];
        $partResult = [];
        foreach ($permission as $pStr) {
            $pParts = explode(".", $pStr);
            foreach ($pParts as $pPart) {
                $nPermStr = !empty($partResult) ? substr($partResult[count($partResult) - 1], 0, -2) . '.' . $pPart : $pPart;
                $partResult[] = $nPermStr . ($pStr != $nPermStr ? '.*' : '');
            }
            $result = array_merge($result, $partResult);
            $partResult = [];
        }
        // super-global permission
        array_unshift($result, '*');
        return $result;
    }

    /**
     * return array of permissions provided for the associated object
     *
     * @return array
     */
    public static function getAllPermissions(): array
    {
        // get base key (e.g. 'users' for User::class etc.)
        $basePermKey = self::getResourceKey();

        // add some global additional permissions here that are being used without context of a resource
        return [
            ['key' => '*', 'name' => 'Manage everything'],
            ['key' => 'system.*', 'name' => 'Manage system'],
            ['key' => 'system.update', 'name' => 'Allow update operations'],
        ];
    }
}
