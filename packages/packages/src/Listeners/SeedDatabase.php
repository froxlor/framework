<?php

namespace Froxlor\Packages\Listeners;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Packages\Database\Seeders\DatabaseSeeder;

class SeedDatabase
{
    public function handle(DatabaseSeeded $event): void
    {
        $event->databaseSeeder->call(DatabaseSeeder::class);
    }
}
