<?php

namespace Froxlor\Core\Events;

use Illuminate\Database\Seeder;

class DatabaseSeeded
{
    /**
     * Create a new event instance.
     */
    public function __construct(public Seeder $databaseSeeder)
    {
        //
    }
}
