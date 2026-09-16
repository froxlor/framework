<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Events\Api\ResourceCreated;
use Froxlor\Core\Events\Api\ResourceDeleted;
use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreTenantRequest;
use Froxlor\Core\Http\Requests\UpdateTenantRequest;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\AdministrationGuard;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Tenant::class);

        return Response::jsonResourceCollection(Tenant::query());
        //return Response::jsonResourceCollection($request->user()->tenants()->with(['plan']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        // get validated data only for ourselves
        $tenantData = $request->validatedResource();
        $nodes = $tenantData['nodes'] ?? [];
        unset($tenantData['nodes']);
        $parentTenant = Tenant::query()->findOrFail($tenantData['parent_tenant_id']);

        Gate::authorize('create', [Tenant::class, $parentTenant]);

        $plan = Plan::query()->findOrFail($tenantData['plan_id']);

        $tenant = DB::transaction(function () use ($tenantData, $nodes, $parentTenant, $plan) {
            PlanAssignments::lockTenantBudget($parentTenant);
            PlanAssignments::ensureAssignableToChildTenant($plan, $parentTenant);

            // create resource
            $tenant = Tenant::query()->create($tenantData);
            PlanAssignments::syncTenantReservations($parentTenant, $tenant, $plan);

            foreach ($nodes as $nodeData) {
                $node = Node::query()->findOrFail($nodeData['id']);
                abort_unless($node->isInheritableByTenant($parentTenant), 422, 'The selected node cannot be inherited by this tenant.');

                $tenant->nodes()->syncWithoutDetaching([
                    $node->id => ['inheritable' => (bool)($nodeData['inheritable'] ?? false)],
                ]);
            }

            return $tenant;
        });
        // build up validated data for others
        $eventData = $this->validatedEventData($request);
        // throw event that resource was created and append validated data
        event(new ResourceCreated($tenant, $eventData));
        Audit::notice('tenant "' . $tenant->name . '" created', $parentTenant, context: [
            'tenant_id' => $tenant->id,
            'plan_id' => $tenant->plan_id,
        ]);

        // return resource
        return Response::jsonResource($tenant->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        Gate::authorize('view', $tenant);

        return Response::jsonResource($tenant->load('plan')->append([
            'tenant_usage_list',
            'all_users_count',
            'all_sub_tenants_count',
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        Gate::authorize('update', $tenant);

        $tenantData = $request->validated();
        $parentTenant = array_key_exists('parent_tenant_id', $tenantData)
            ? Tenant::query()->find($tenantData['parent_tenant_id'])
            : $tenant->parentTenant;

        if (!$tenant->canHaveParent($parentTenant)) {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'A tenant cannot be assigned to itself or one of its descendants.',
            ]);
        }

        $plan = array_key_exists('plan_id', $tenantData)
            ? Plan::query()->findOrFail($tenantData['plan_id'])
            : $tenant->plan;
        $oldParentTenant = $tenant->parentTenant;

        AdministrationGuard::run(function () use ($tenant, $tenantData, $oldParentTenant, $parentTenant, $plan): void {
            Gate::authorize('update', $tenant);
            if ($oldParentTenant !== null && ($parentTenant === null || $oldParentTenant->id !== $parentTenant->id)) {
                PlanAssignments::lockTenantBudget($oldParentTenant);
            }

            if ($parentTenant !== null) {
                PlanAssignments::lockTenantBudget($parentTenant);
                PlanAssignments::ensureAssignableToChildTenant($plan, $parentTenant, $tenant);
            }

            if ($oldParentTenant !== null) {
                PlanAssignments::removeTenantReservations($oldParentTenant, $tenant);
            }

            $tenant->update($tenantData);

            if ($parentTenant !== null) {
                PlanAssignments::syncTenantReservations($parentTenant, $tenant->refresh(), $plan);
            }

        });
        event(new ResourceUpdated($tenant, $this->validatedEventData($request)));
        $tenant->refresh();
        Audit::info('tenant "' . $tenant->name . '" updated', $tenant->parentTenant, context: [
            'tenant_id' => $tenant->id,
            'plan_id' => $tenant->plan_id,
        ]);

        return Response::jsonResource($tenant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        Gate::authorize('delete', $tenant);

        if ($tenant->subTenants()->exists()) {
            throw ValidationException::withMessages([
                'tenant' => 'A tenant with child tenants cannot be deleted.',
            ]);
        }

        $parentTenant = $tenant->parentTenant;
        AdministrationGuard::run(function () use ($tenant): void {
            Gate::authorize('delete', $tenant);
            $tenant->delete();
        });
        event(new ResourceDeleted($tenant, []));
        Audit::info('tenant "' . $tenant->name . '" deleted', $parentTenant, context: [
            'tenant_id' => $tenant->id,
        ]);

        return response()->noContent();
    }
}
