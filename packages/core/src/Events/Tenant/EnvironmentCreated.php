<?php

namespace Froxlor\Core\Events\Tenant;

use Froxlor\Core\Models\Environment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnvironmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Environment $environment)
    {
        //
    }
}
