<?php

namespace Froxlor\Mail\Listeners;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Mail\Database\Seeders\DatabaseSeeder;

class SeedDatabase
{
    public function handle(DatabaseSeeded $event): void
    {
        $event->databaseSeeder->call(DatabaseSeeder::class);
    }
}
