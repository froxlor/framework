<?php

namespace Froxlor\Database\Observers;

use Froxlor\Database\Events;
use Froxlor\Database\Models\DatabaseServer;

class DatabaseServerObserver
{
    public function created(DatabaseServer $databaseServer): void
    {
        event(new Events\DatabaseServer\DatabaseServerCreated($databaseServer));
    }

    public function updated(DatabaseServer $databaseServer): void
    {
        event(new Events\DatabaseServer\DatabaseServerUpdated($databaseServer));
    }

    public function deleted(DatabaseServer $databaseServer): void
    {
        event(new Events\DatabaseServer\DatabaseServerDeleted($databaseServer));
    }

    public function restored(DatabaseServer $databaseServer): void
    {
        event(new Events\DatabaseServer\DatabaseServerRestored($databaseServer));
    }

    public function forceDeleted(DatabaseServer $databaseServer): void
    {
        event(new Events\DatabaseServer\DatabaseServerForceDeleted($databaseServer));
    }
}
