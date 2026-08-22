<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Exception;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Resources\RepositoryResource;
use Froxlor\Packages\Services\PackageService;
use Froxlor\UI\Support\UI;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function index()
    {
        return UI::render(RepositoryResource::class, 'index');
    }

    public function create()
    {
        return UI::render(RepositoryResource::class, 'create');
    }

    public function edit(string $repository)
    {
        return UI::render(RepositoryResource::class, 'edit', [
            'repository' => $repository,
        ]);
    }

    public function switch(Request $request, PackageService $packageService)
    {
        $response = match($request->type) {
            'stable' => $packageService->changeToDefaultRepository(),
            'developer' => $packageService->loadPackageRepository(),
        };

        return back()->with('message', [$response['status'], $response['message']]);
    }

    public function destroy(string $repository, PackageService $packageService)
    {
        try {
            $packageService->removeRepository(str_replace(':', '/', $repository));
        } catch (Exception $e) {
            return back()->with('message', ['error', $e->getMessage()]);
        }

        return back()->with('message', ['success', trans('froxlor-packages::generic.repository_deleted_successfully')]);
    }
}
