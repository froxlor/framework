<?php

namespace Froxlor\Core\Events\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionResponseMade
{
    public function __construct(public AnonymousResourceCollection $collection)
    {
        //
    }
}
