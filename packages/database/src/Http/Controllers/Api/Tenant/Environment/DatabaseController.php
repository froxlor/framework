<?php

namespace Froxlor\Database\Http\Controllers\Api\Tenant\Environment;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Froxlor\Database\Http\Controllers\Controller;
use Froxlor\Database\Models\DatabaseServer;
use Froxlor\Database\Http\Requests\StoreDatabaseRequest;
use Froxlor\Database\Http\Requests\UpdateDatabaseRequest;
use Froxlor\Database\Models\Database;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function index(Request $request, Tenant $tenant, Environment $environment)
    {
        $this->assertEnvironmentBelongsToTenant($tenant, $environment);

        return Response::jsonResourceCollection(
            $environment->databases()->with('databaseServer')
        );
    }

    public function store(StoreDatabaseRequest $request, Tenant $tenant, Environment $environment)
    {
        $this->assertEnvironmentBelongsToTenant($tenant, $environment);

        $database = $environment->databases()->create(
            $this->normalizedData($environment, $request->validated())
        );

        return Response::jsonResource($database->load('databaseServer'));
    }

    public function show(Request $request, Tenant $tenant, Environment $environment, Database $database)
    {
        $this->assertEnvironmentBelongsToTenant($tenant, $environment);
        $this->assertDatabaseBelongsToEnvironment($environment, $database);

        return Response::jsonResource($database->load(['environment', 'databaseServer']));
    }

    public function update(UpdateDatabaseRequest $request, Tenant $tenant, Environment $environment, Database $database)
    {
        $this->assertEnvironmentBelongsToTenant($tenant, $environment);
        $this->assertDatabaseBelongsToEnvironment($environment, $database);

        $database->update($this->normalizedData($environment, $request->validated(), false));

        return Response::jsonResource($database->fresh()->load('databaseServer'));
    }

    public function destroy(Request $request, Tenant $tenant, Environment $environment, Database $database)
    {
        $this->assertEnvironmentBelongsToTenant($tenant, $environment);
        $this->assertDatabaseBelongsToEnvironment($environment, $database);

        $database->delete();

        return response()->noContent();
    }

    private function assertEnvironmentBelongsToTenant(Tenant $tenant, Environment $environment): void
    {
        abort_if($environment->tenant_id !== $tenant->id, 404);
    }

    private function assertDatabaseBelongsToEnvironment(Environment $environment, Database $database): void
    {
        abort_if($database->environment_id !== $environment->id, 404);
    }

    private function normalizedData(Environment $environment, array $data, bool $withDefaults = true): array
    {
        [$mainNode, $databaseService] = $this->resolveDatabaseService($environment);

        $data['database_server_id'] = $databaseService->id;

        if ($withDefaults) {
            $data['database_name'] = $data['database_name'] ?? $data['name'];
            $data['username'] = $data['username'] ?? $data['name'];
            $data['engine'] = $data['engine'] ?? 'mysql';
            $data['charset'] = $data['charset'] ?? 'utf8mb4';
            $data['collation'] = $data['collation'] ?? 'utf8mb4_unicode_ci';
            $data['status'] = $data['status'] ?? 'draft';
        }

        return $data;
    }

    /**
     * @return array{0: Node, 1: DatabaseServer}
     */
    private function resolveDatabaseService(Environment $environment): array
    {
        $mainNode = $environment->nodes()->wherePivot('mode', 'main')->first() ?? $environment->nodes()->first();

        abort_if(! $mainNode, 422, 'Environment has no node assigned.');
        abort_if(! $mainNode->databaseServer, 422, 'The assigned node has no database service configured.');

        return [$mainNode, $mainNode->databaseServer];
    }
}
