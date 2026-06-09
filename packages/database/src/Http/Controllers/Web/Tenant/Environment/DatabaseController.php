<?php

namespace Froxlor\Database\Http\Controllers\Web\Tenant\Environment;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Database\Models\Database;
use Froxlor\Database\Resources\Tenants\Relations\Databases\DatabaseResource;
use Froxlor\Database\Http\Controllers\Controller;
use Froxlor\UI\Support\UI;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function index(Request $request, Tenant $tenant, Environment $environment)
    {
        return UI::render(DatabaseResource::class, 'index', [$tenant, $environment]);
    }

    public function create(Tenant $tenant, Environment $environment)
    {
        return UI::render(DatabaseResource::class, 'create', [$tenant, $environment]);
    }

    public function show(Tenant $tenant, Environment $environment, Database $database)
    {
        return UI::render(DatabaseResource::class, 'show', [$tenant, $environment, $database]);
    }

    public function edit(Tenant $tenant, Environment $environment, Database $database)
    {
        return UI::render(DatabaseResource::class, 'edit', [$tenant, $environment, $database]);
    }
}
