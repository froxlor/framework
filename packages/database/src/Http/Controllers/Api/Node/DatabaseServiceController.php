<?php

namespace Froxlor\Database\Http\Controllers\Api\Node;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Response;
use Froxlor\Database\Http\Controllers\Controller;
use Froxlor\Database\Http\Requests\StoreDatabaseServerRequest;
use Froxlor\Database\Http\Requests\UpdateDatabaseServerRequest;
use Froxlor\Database\Jobs\DatabaseService\CheckDatabaseService;
use Froxlor\Database\Jobs\DatabaseService\ConfigureDatabaseService;
use Froxlor\Database\Jobs\DatabaseService\InstallDatabaseService;
use Illuminate\Http\Request;

class DatabaseServiceController extends Controller
{
    public function show(Request $request, Node $node)
    {
        abort_if(! $node->databaseServer, 404);

        return Response::jsonResource($node->databaseServer->load('databases'));
    }

    public function store(StoreDatabaseServerRequest $request, Node $node)
    {
        abort_if($node->databaseServer()->exists(), 422, 'Database service already configured for this node.');

        $databaseService = $node->databaseServer()->create($this->normalizedData($request->validated()));

        return Response::jsonResource($databaseService);
    }

    public function update(UpdateDatabaseServerRequest $request, Node $node)
    {
        $databaseService = $node->databaseServer;

        abort_if(! $databaseService, 404);

        $databaseService->update($this->normalizedData($request->validated(), false));

        return Response::jsonResource($databaseService->fresh());
    }

    public function install(Request $request, Node $node)
    {
        $databaseService = $this->databaseService($node);
        $databaseService->forceFill([
            'status' => 'install_queued',
            'last_error' => null,
        ])->save();

        dispatch(new InstallDatabaseService($databaseService->fresh()));

        return Response::jsonResource($databaseService->fresh());
    }

    public function configure(Request $request, Node $node)
    {
        $databaseService = $this->databaseService($node);
        $databaseService->forceFill([
            'status' => 'configure_queued',
            'last_error' => null,
        ])->save();

        dispatch(new ConfigureDatabaseService($databaseService->fresh()));

        return Response::jsonResource($databaseService->fresh());
    }

    public function check(Request $request, Node $node)
    {
        $databaseService = $this->databaseService($node);
        $databaseService->forceFill([
            'status' => 'check_queued',
            'last_error' => null,
        ])->save();

        dispatch(new CheckDatabaseService($databaseService->fresh()));

        return Response::jsonResource($databaseService->fresh());
    }

    public function destroy(Request $request, Node $node)
    {
        $databaseService = $this->databaseService($node);

        $databaseService->delete();

        return response()->noContent();
    }

    private function normalizedData(array $data, bool $withDefaults = true): array
    {
        if ($withDefaults) {
            $data['driver'] = $data['driver'] ?? 'mysql';
            $data['status'] = $data['status'] ?? 'defined';
            $data['supports_per_environment_users'] = $data['supports_per_environment_users'] ?? true;
        }

        return $data;
    }

    private function databaseService(Node $node)
    {
        abort_if(! $node->databaseServer, 404);

        return $node->databaseServer;
    }
}
