<?php

namespace Froxlor\Database\Jobs\DatabaseService;

use Froxlor\Database\Models\DatabaseServer;
use Froxlor\Database\Services\DatabaseServiceLifecycle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConfigureDatabaseService implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DatabaseServer $databaseServer)
    {
    }

    public function handle(DatabaseServiceLifecycle $lifecycle): void
    {
        $lifecycle->configure($this->databaseServer->fresh());
    }
}
