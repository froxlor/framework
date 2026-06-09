<?php

namespace Froxlor\Core\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\Tenants\Relations\Users\UserResource;
use Froxlor\UI\Support\UI;

class UserController extends Controller
{
    public function index(Tenant $tenant)
    {
        return UI::render(UserResource::class, 'index', [$tenant]);
    }

    public function create(Tenant $tenant)
    {
        return UI::render(UserResource::class, 'create', [$tenant]);
    }

    public function show(Tenant $tenant, User $user)
    {
        return UI::render(UserResource::class, 'show', [$tenant, $user]);
    }

    public function edit(Tenant $tenant, User $user)
    {
        return UI::render(UserResource::class, 'edit', [$tenant, $user]);
    }
}
