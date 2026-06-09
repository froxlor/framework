<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\TenantResource;
use Froxlor\UI\Support\UI;

class TenantController extends Controller
{
    public function index()
    {
        return UI::render(TenantResource::class, 'index');
    }

    public function create()
    {
        return UI::render(TenantResource::class, 'create');
    }

    public function show(Tenant $tenant)
    {
        $tenant = $tenant->load(['environments', 'users', 'subTenants', 'plan', 'roles']);

        return UI::render(TenantResource::class, 'show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return UI::render(TenantResource::class, 'edit', [
            'tenant' => $tenant,
        ]);
    }
}
