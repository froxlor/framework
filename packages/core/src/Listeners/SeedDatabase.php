<?php

namespace Froxlor\Core\Listeners;

use Froxlor\Core\Database\Seeders\DatabaseSeeder;
use Froxlor\Core\Events\DatabaseSeeding;

class SeedDatabase
{
    public function handle(DatabaseSeeding $event): void
    {
        $event->databaseSeeder->call(DatabaseSeeder::class);
    }
}
