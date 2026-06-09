<?php

namespace Froxlor\Core\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\Environments\EnvironmentResource;
use Froxlor\UI\Support\UI;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        return UI::render(EnvironmentResource::class, 'index', [$request, $tenant]);
    }

    public function create(Tenant $tenant)
    {
        return UI::render(EnvironmentResource::class, 'create', [$tenant]);
    }

    public function show(Tenant $tenant, Environment $environment)
    {
        return UI::render(EnvironmentResource::class, 'show', [$tenant, $environment]);
    }

    public function edit(Tenant $tenant, Environment $environment)
    {
        return UI::render(EnvironmentResource::class, 'edit', [$tenant, $environment]);
    }
}
