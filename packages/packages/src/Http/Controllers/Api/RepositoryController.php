<?php

namespace Froxlor\Packages\Http\Controllers\Api;

use Exception;
use Froxlor\Core\Support\Response;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Http\Requests\StoreRepositoryRequest;
use Froxlor\Packages\Http\Requests\UpdateRepositoryRequest;
use Froxlor\Packages\Services\PackageService;

class RepositoryController extends Controller
{
    public function index(PackageService $packageService)
    {
        return Response::jsonResource($packageService->repositories());
    }

    public function show(string $repository, PackageService $packageService)
    {
        $found = $packageService->findRepository(str_replace(':', '/', $repository));

        if (!$found) {
            return response()->noContent();
        }

        return Response::jsonResource($found);
    }

    public function store(StoreRepositoryRequest $request, PackageService $packageService)
    {
        $data = $request->validated();

        try {
            $packageService->addRepository($data['name'], $data['type'], $data['url'], $data['options'] ?? null);

            $this->applyAuth($packageService, $data);
        } catch (Exception $e) {
            return response()->json(['errors' => ['name' => $e->getMessage()]], 409);
        }

        return Response::jsonResource($packageService->findRepository($data['name']));
    }

    public function update(UpdateRepositoryRequest $request, string $repository, PackageService $packageService)
    {
        $name = str_replace(':', '/', $repository);
        $data = $request->validated();

        try {
            if ($name !== $data['name']) {
                $packageService->removeRepository($name);
            }

            $packageService->addRepository($data['name'], $data['type'], $data['url'], $data['options'] ?? null);

            $this->applyAuth($packageService, $data);
        } catch (Exception $e) {
            return response()->json(['errors' => ['name' => $e->getMessage()]], 409);
        }

        return Response::jsonResource($packageService->findRepository($data['name']));
    }

    public function destroy(string $repository, PackageService $packageService)
    {
        try {
            $packageService->removeRepository(str_replace(':', '/', $repository));
        } catch (Exception $e) {
            return response()->json(['errors' => ['name' => $e->getMessage()]], 409);
        }

        return response()->noContent();
    }

    /**
     * @throws Exception
     */
    private function applyAuth(PackageService $packageService, array $data): void
    {
        if (!$auth = $data['auth'] ?? null) {
            return;
        }

        $host = parse_url($data['url'], PHP_URL_HOST) ?: $data['url'];

        $packageService->setRepositoryAuth($host, $auth['type'], $auth);
    }
}
