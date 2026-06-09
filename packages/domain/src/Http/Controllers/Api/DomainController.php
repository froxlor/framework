<?php

namespace Froxlor\Domain\Http\Controllers\Api;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Core\Services\Traits\TenantAccessPermission;
use Froxlor\Core\Support\Response;
use Froxlor\Domain\Http\Requests\StoreDomainRequest;
use Froxlor\Domain\Http\Requests\UpdateDomainRequest;
use Froxlor\Domain\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends CoreController
{
    use TenantAccessPermission;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Response::jsonResourceCollection(Domain::query());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDomainRequest $request)
    {
        // get validated data only for ourselves
        $domainData = $request->validatedResource();
        // create resource
        $domain = Domain::query()->create($domainData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new CoreEvents\Api\ResourceCreated($domain, $eventData));

        // return resource
        return Response::jsonResource($domain->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Domain $domain)
    {
        return Response::jsonResource($domain->load(['tenant', 'environment']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $domain->update($request->validated());

        return Response::jsonResource($domain->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Domain $domain)
    {
        $domain->delete();

        return response()->noContent();
    }
}
