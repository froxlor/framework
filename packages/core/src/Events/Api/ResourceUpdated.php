<?php

namespace Froxlor\Core\Events\Api;

use Illuminate\Database\Eloquent\Model;

class ResourceUpdated
{
    public function __construct(public Model $model, public array $validatedData)
    {
        //
    }
}
