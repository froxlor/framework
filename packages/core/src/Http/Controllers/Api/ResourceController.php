<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Support\Response;
use Illuminate\Support\Facades\Gate;

class ResourceController extends Controller
{
    /**
     * Display the package-provided resources available for plan editing.
     */
    public function index()
    {
        Gate::authorize('availableResourcesViewAny', Plan::class);

        return Response::jsonResourceCollection(Resource::query()->orderBy('type')->orderBy('key'));
    }
}
