<?php

namespace Froxlor\Packages\Http\Controllers\Api;

use Froxlor\Core\Support\Response;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Models\Repository;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function index()
    {
        return Response::jsonResourceCollection(
            Repository::query()
        );
    }

    public function show(Repository $repository)
    {
        return Response::jsonResource(
            $repository
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'url' => 'required|string',
            'options' => 'nullable|array',
            'auth' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $repository = Repository::query()->create($data);

        return Response::jsonResource($repository->refresh());
    }

    public function update(Request $request, Repository $repository)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
            'url' => 'sometimes|string',
            'options' => 'sometimes|nullable|array',
            'auth' => 'sometimes|nullable|array',
            'enabled' => 'sometimes|boolean',
        ]);

        $repository->update($data);

        return Response::jsonResource($repository->refresh());
    }

    public function destroy(Repository $repository)
    {
        $repository->delete();

        return response()->noContent();
    }
}
