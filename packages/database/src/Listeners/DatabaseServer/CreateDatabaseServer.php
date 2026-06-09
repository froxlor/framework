<?php

namespace Froxlor\Database\Listeners\DatabaseServer;

use Froxlor\Database\Events\Database\DatabaseCreated;

class CreateDatabaseServer
{
    public function handle(DatabaseCreated $event): void
    {
        // hier datenbank erstellen...

        dd('database create listener', $event, $event->database);

        // $event->database->environment...
    }
}
