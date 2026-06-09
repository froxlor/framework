<?php

namespace Froxlor\Database\Events\Database;

use Froxlor\Database\Models\Database;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Database $database)
    {
        //
    }
}
