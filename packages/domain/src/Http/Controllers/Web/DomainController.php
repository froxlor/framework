<?php

namespace Froxlor\Domain\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Domain\Models\Domain;
use Froxlor\Domain\Resources\DomainResource;
use Froxlor\UI\Support\UI;

class DomainController extends CoreController
{
    public function index()
    {
        return UI::render(DomainResource::class, 'index');
    }

    public function create()
    {
        return UI::render(DomainResource::class, 'create');
    }

    public function show(Domain $domain)
    {
        return UI::render(DomainResource::class, 'show', [$domain]);
    }

    public function edit(Domain $domain)
    {
        return UI::render(DomainResource::class, 'edit', [$domain]);
    }
}
