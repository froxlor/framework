<?php

namespace Froxlor\Domain\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models\Domain;
use Froxlor\Domain\Resources\TenantDomainResource;
use Froxlor\UI\Support\UI;

class DomainController extends CoreController
{
    public function index(Tenant $tenant)
    {
        return UI::render(TenantDomainResource::class, 'index', [$tenant]);
    }

    public function create(Tenant $tenant)
    {
        return UI::render(TenantDomainResource::class, 'create', [$tenant]);
    }

    public function show(Tenant $tenant, Domain $domain)
    {
        return UI::render(TenantDomainResource::class, 'show', [$tenant, $domain]);
    }

    public function edit(Tenant $tenant, Domain $domain)
    {
        return UI::render(TenantDomainResource::class, 'edit', [$tenant, $domain]);
    }
}
