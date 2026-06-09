<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Resources\Plans\PlanResource;
use Froxlor\UI\Support\UI;

class PlanController extends Controller
{
    public function index()
    {
        return UI::render(PlanResource::class, 'index');
    }

    public function create()
    {
        return UI::render(PlanResource::class, 'create');
    }

    public function show(Plan $plan)
    {
        return UI::render(PlanResource::class, 'show', [
            'plan' => $plan
        ]);
    }

    public function edit(Plan $plan)
    {
        return UI::render(PlanResource::class, 'edit', [
            'plan' => $plan
        ]);
    }
}
