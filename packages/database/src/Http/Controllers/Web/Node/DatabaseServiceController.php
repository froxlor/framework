<?php

namespace Froxlor\Database\Http\Controllers\Web\Node;

use Froxlor\Core\Models\Node;
use Froxlor\Database\Http\Controllers\Controller;
use Froxlor\Database\Jobs\DatabaseService\CheckDatabaseService;
use Froxlor\Database\Jobs\DatabaseService\ConfigureDatabaseService;
use Froxlor\Database\Jobs\DatabaseService\InstallDatabaseService;
use Froxlor\Database\Resources\Nodes\DatabaseServiceResource;
use Froxlor\UI\Support\UI;
use Illuminate\Http\RedirectResponse;

class DatabaseServiceController extends Controller
{
    public function show(Node $node)
    {
        if (! $node->databaseServer) {
            return UI::render(DatabaseServiceResource::class, 'create', [$node]);
        }

        return UI::render(DatabaseServiceResource::class, 'show', [$node]);
    }

    public function create(Node $node)
    {
        return UI::render(DatabaseServiceResource::class, 'create', [$node]);
    }

    public function edit(Node $node)
    {
        if (! $node->databaseServer) {
            return UI::render(DatabaseServiceResource::class, 'create', [$node]);
        }

        return UI::render(DatabaseServiceResource::class, 'edit', [$node]);
    }

    public function install(Node $node): RedirectResponse
    {
        abort_if(! $node->databaseServer, 404);

        $node->databaseServer->forceFill([
            'status' => 'install_queued',
            'last_error' => null,
        ])->save();

        dispatch(new InstallDatabaseService($node->databaseServer->fresh()));

        return redirect()->route('resources.nodes.database-service.show', ['node' => $node]);
    }

    public function configure(Node $node): RedirectResponse
    {
        abort_if(! $node->databaseServer, 404);

        $node->databaseServer->forceFill([
            'status' => 'configure_queued',
            'last_error' => null,
        ])->save();

        dispatch(new ConfigureDatabaseService($node->databaseServer->fresh()));

        return redirect()->route('resources.nodes.database-service.show', ['node' => $node]);
    }

    public function check(Node $node): RedirectResponse
    {
        abort_if(! $node->databaseServer, 404);

        $node->databaseServer->forceFill([
            'status' => 'check_queued',
            'last_error' => null,
        ])->save();

        dispatch(new CheckDatabaseService($node->databaseServer->fresh()));

        return redirect()->route('resources.nodes.database-service.show', ['node' => $node]);
    }
}
