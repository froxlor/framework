<?php

namespace Froxlor\UI\Concerns;

trait HasCallable
{
    protected function call(mixed $value): mixed
    {
        if ($value instanceof \Closure) {
            return app()->call($value);
        }

        return $value;
    }
}
