<?php

namespace Froxlor\Core\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\Plans\PlanResource;
use Froxlor\UI\Support\UI;

class PlanController extends Controller
{
    public function index(Tenant $tenant)
    {
        return UI::render(PlanResource::class, 'index', ['tenant' => $tenant]);
    }

    public function show(Tenant $tenant, Plan $plan)
    {
        return UI::render(PlanResource::class, 'show', [
            'tenant' => $tenant,
            'plan' => $plan
        ]);
    }

}
