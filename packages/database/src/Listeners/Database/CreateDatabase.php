<?php

namespace Froxlor\Database\Listeners\Database;

use Froxlor\Database\Events\Database\DatabaseCreated;

class CreateDatabase
{
    public function handle(DatabaseCreated $event): void
    {
        // hier datenbank erstellen...

        // dd('database create listener', $event, $event->database);

        // $event->database->environment...
    }
}
