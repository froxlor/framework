<?php

namespace Froxlor\UI\Support;

use Closure;

class AttributeResolver
{
    /**
     * Resolve a value if it is a Closure, otherwise return it as-is.
     */
    public static function value(mixed $value, array $context = []): mixed
    {
        if (is_array($value)) {
            return array_map(fn($item) => self::value($item, $context), $value);
        }

        if ($value instanceof Closure) {
            return app()->call($value, $context);
        }

        return $value;
    }
}
