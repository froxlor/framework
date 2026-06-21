<?php

namespace Froxlor\Core\Http\Controllers\Api\Plan;

use Froxlor\Core\Events\Api\ResourceUpdated;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PlanResourceController extends Controller
{
    /**
     * Display every available resource and mark assigned resources.
     */
    public function index(Request $request, Plan $plan)
    {
        Gate::authorize('resourceViewAny', $plan);

        if ($request->query('ids_only', false)) {
            return response()->json([
                'data' => $plan->resources()->pluck('resources.id'),
            ]);
        }

        $assignedResources = $plan->resources()
            ->select(['resources.id'])
            ->get()
            ->mapWithKeys(fn(Resource $resource) => [
                $resource->id => (int)$resource->pivot->limit,
            ]);

        $resources = Resource::query()
            ->orderBy('type')
            ->orderBy('key')
            ->get()
            ->map(function (Resource $resource) use ($assignedResources) {
                $resource->assigned = $assignedResources->has($resource->id);
                $resource->limit = $assignedResources->get($resource->id, 0);

                return $resource;
            });

        return JsonResource::collection($resources);
    }

    /**
     * Assign or update a resource limit on a global plan.
     */
    public function store(Request $request, Plan $plan)
    {
        Gate::authorize('resourceCreate', $plan);

        $data = $request->validate([
            'resource_id' => 'required|string|ulid|exists:resources,id',
            'limit' => 'required|integer|min:-1',
        ]);

        $resource = Resource::query()->findOrFail($data['resource_id']);
        PlanAssignments::ensureResourceCanBeAttached($plan, $resource, (int)$data['limit']);

        $plan->resources()->syncWithoutDetaching([
            $resource->id => ['limit' => (int)$data['limit']],
        ]);

        Audit::log('resource "' . $resource->key . '" assigned to plan "' . $plan->name . '"', $plan->tenant, context: [
            'plan_id' => $plan->id,
            'resource_id' => $resource->id,
            'resource_key' => $resource->key,
            'limit' => (int)$data['limit'],
        ]);
        event(new ResourceUpdated($plan, []));

        return Response::jsonResourceCollection($plan->resources());
    }

    /**
     * Remove a resource from a global plan.
     */
    public function destroy(Plan $plan, Resource $resource)
    {
        Gate::authorize('resourceDelete', [$plan, $resource]);

        if (!$plan->resources()->where('resources.id', $resource->id)->exists()) {
            throw ValidationException::withMessages([
                'resource_id' => 'The selected resource is not assigned to this plan.',
            ]);
        }

        $plan->resources()->detach($resource);

        Audit::log('resource "' . $resource->key . '" removed from plan "' . $plan->name . '"', $plan->tenant, context: [
            'plan_id' => $plan->id,
            'resource_id' => $resource->id,
            'resource_key' => $resource->key,
        ]);
        event(new ResourceUpdated($plan, []));

        return Response::jsonResourceCollection($plan->resources());
    }
}
