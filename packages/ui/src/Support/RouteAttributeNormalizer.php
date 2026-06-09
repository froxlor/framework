<?php

namespace Froxlor\UI\Support;

use Illuminate\Contracts\Routing\UrlRoutable;
use UnitEnum;

class RouteAttributeNormalizer
{
    public static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(static fn (mixed $item): mixed => self::normalize($item), $value);
        }

        if ($value instanceof UrlRoutable) {
            return $value->getRouteKey();
        }

        if ($value instanceof UnitEnum) {
            return property_exists($value, 'value') ? $value->value : $value->name;
        }

        return $value;
    }
}
