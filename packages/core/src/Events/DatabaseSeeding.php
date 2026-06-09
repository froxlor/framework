<?php

namespace Froxlor\Core\Events;

use Illuminate\Database\Seeder;

class DatabaseSeeding
{
    /**
     * Create a new event instance.
     */
    public function __construct(public Seeder $databaseSeeder)
    {
        //
    }
}
