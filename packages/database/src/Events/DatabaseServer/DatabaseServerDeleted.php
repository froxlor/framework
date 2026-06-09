<?php

namespace Froxlor\Database\Events\DatabaseServer;

use Froxlor\Database\Models\DatabaseServer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseServerDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DatabaseServer $databaseServer)
    {
        //
    }
}
