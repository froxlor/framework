<?php

namespace Froxlor\Core\Events\System;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FroxlorUpdateFound
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $version_info)
    {
        Log::debug('Fireing event "' . __CLASS__ . '"');
    }
}
