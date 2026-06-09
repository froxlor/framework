<?php

namespace Froxlor\Core\Events\Api;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ResourceCreated
{
    public function __construct(public Model $model, public array $validatedData)
    {
        //
    }
}
