<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreResourceRequest;
use Froxlor\Core\Http\Requests\UpdateResourceRequest;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Support\Response;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Response::jsonResourceCollection(Resource::query());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $resource = Resource::query()->create($request->validated());

        return Response::jsonResource($resource->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        return Response::jsonResource($resource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $resource->update($request->validated());

        return Response::jsonResource($resource->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $resource->delete();

        return response()->noContent();
    }
}
