<?php

namespace Froxlor\Core\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\Roles\RoleResource;
use Froxlor\UI\Support\UI;

class RoleController extends Controller
{
    public function index(Tenant $tenant)
    {
        return UI::render(RoleResource::class, 'index', [$tenant]);
    }

    public function create(Tenant $tenant)
    {
        return UI::render(RoleResource::class, 'create', [$tenant]);
    }

    public function show(Tenant $tenant, Role $role)
    {
        return UI::render(RoleResource::class, 'show', [$tenant, $role]);
    }

    public function edit(Tenant $tenant, Role $role)
    {
        return UI::render(RoleResource::class, 'edit', [$tenant, $role]);
    }
}
