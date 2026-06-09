<?php

namespace Froxlor\Domain\Http\Controllers\Web\Tenant\Environment;

use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models\Domain;
use Froxlor\Domain\Resources\EnvironmentDomainResource;
use Froxlor\UI\Support\UI;

class DomainController extends CoreController
{
    public function index(Tenant $tenant, Environment $environment)
    {
        return UI::render(EnvironmentDomainResource::class, 'index', [$tenant, $environment]);
    }

    public function create(Tenant $tenant, Environment $environment)
    {
        return UI::render(EnvironmentDomainResource::class, 'create', [$tenant, $environment]);
    }

    public function show(Tenant $tenant, Environment $environment, Domain $domain)
    {
        return UI::render(EnvironmentDomainResource::class, 'show', [$tenant, $environment, $domain]);
    }

    public function edit(Tenant $tenant, Environment $environment, Domain $domain)
    {
        return UI::render(EnvironmentDomainResource::class, 'edit', [$tenant, $environment, $domain]);
    }
}
