<?php

namespace Froxlor\Web\Listeners;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Web\Database\Seeders\DatabaseSeeder;

class SeedDatabase
{
    public function handle(DatabaseSeeded $event): void
    {
        $event->databaseSeeder->call(DatabaseSeeder::class);
    }
}
