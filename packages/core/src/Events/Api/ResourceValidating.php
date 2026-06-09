<?php

namespace Froxlor\Core\Events\Api;

class ResourceValidating
{
    public function __construct(public string $class, public string $action = 'store', public array &$rules = [])
    {
        //
    }
}
