<?php

namespace Froxlor\Core\Events\Api;

use Froxlor\Core\Models\Environment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Queue\SerializesModels;

class ResourceResponseMade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public JsonResource $resource)
    {
        //
    }
}
