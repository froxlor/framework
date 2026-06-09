<?php

namespace Froxlor\Domain\Http\Controllers\Api\Tenant;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Domain\Http\Requests\UpdateDomainRequest;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Traits\TenantAccessPermission;
use Froxlor\Core\Support\Response;
use Froxlor\Domain\Http\Requests\Tenant\StoreTenantDomainRequest;
use Froxlor\Domain\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends CoreController
{
    use TenantAccessPermission;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        return Response::jsonResourceCollection($tenant->domains());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantDomainRequest $request, Tenant $tenant)
    {
        // get validated data only for ourselves
        $domainData = $request->validatedResource();
        // fixed values
        $domainData['tenant_id'] = $tenant->id;
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
    public function show(Request $request, Tenant $tenant, Domain $domain)
    {
        return Response::jsonResource($domain->load(['environment']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDomainRequest $request, Tenant $tenant, Domain $domain)
    {
        $domainData = $request->validated();
        unset($domainData['tenant_id']);

        $domain->update($domainData);

        return Response::jsonResource($domain->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tenant $tenant, Domain $domain)
    {
        $domain->delete();

        return response()->noContent();
    }
}
