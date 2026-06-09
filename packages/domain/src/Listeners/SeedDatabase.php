<?php

namespace Froxlor\Domain\Listeners;

use Froxlor\Core\Events\DatabaseSeeded;
use Froxlor\Domain\Database\Seeders\DatabaseSeeder;

class SeedDatabase
{
    public function handle(DatabaseSeeded $event): void
    {
        $event->databaseSeeder->call(DatabaseSeeder::class);
    }
}
