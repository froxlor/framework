<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Models\Repository;
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

    public function edit(Repository $repository)
    {
        return UI::render(RepositoryResource::class, 'edit', [
            'repository' => $repository
        ]);
    }

    public function update(PackageService $packageService)
    {
        $response = $packageService->updateRepositories();

        return back()->with('message', [$response['status'], $response['message']]);
    }

    public function switch(Request $request, PackageService $packageService)
    {
        $response = match($request->type) {
            'stable' => $packageService->changeToDefaultRepository(),
            'developer' => $packageService->changeToLocalRepository(),
        };

        return back()->with('message', [$response['status'], $response['message']]);
    }

    public function destroy(Repository $repository)
    {
        $repository->delete();

        return back()->with('message', ['success', trans('froxlor-packages::generic.repository_deleted_successfully')]);
    }
}
