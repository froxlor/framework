<?php

namespace Froxlor\Core\Services\Traits;

use Illuminate\Support\Str;

trait IsResource
{
    /**
     * returns standard resource-key name of the model, which by default is the lowercased plural version of the classname
     *
     * @return string
     */
    public static function getResourceKey(): string
    {
        return Str::plural(strtolower((class_basename(static::class))));
    }
}
