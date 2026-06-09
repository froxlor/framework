<?php

namespace Froxlor\Core\Services\Traits;

trait HasPermissions
{
    /**
     * return array of permissions provided for the associated object
     *
     * @return array
     */
    public static function getAllPermissions(): array
    {
        // get base key (e.g. 'users' for User::class etc.)
        $basePermKey = self::getResourceKey();

        return [
            ['key' => $basePermKey . '.*', 'name' => 'Manage ' . $basePermKey],
            ['key' => $basePermKey . '.index', 'name' => 'View ' . $basePermKey],
            ['key' => $basePermKey . '.store', 'name' => 'Create ' . $basePermKey],
            ['key' => $basePermKey . '.update', 'name' => 'Update ' . $basePermKey],
            ['key' => $basePermKey . '.destroy', 'name' => 'Delete ' . $basePermKey],
        ];
    }
}
