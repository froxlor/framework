<?php

namespace Froxlor\Database\Observers;

use Froxlor\Database\Events;
use Froxlor\Database\Models\Database;

class DatabaseObserver
{
    public function created(Database $database): void
    {
        event(new Events\Database\DatabaseCreated($database));
    }

    public function updated(Database $database): void
    {
        event(new Events\Database\DatabaseUpdated($database));
    }

    public function deleted(Database $database): void
    {
        event(new Events\Database\DatabaseDeleted($database));
    }

    public function restored(Database $database): void
    {
        event(new Events\Database\DatabaseRestored($database));
    }

    public function forceDeleted(Database $database): void
    {
        event(new Events\Database\DatabaseForceDeleted($database));
    }
}
