<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Resources\Roles\RoleResource;
use Froxlor\UI\Support\UI;

class RoleController extends Controller
{
    public function index()
    {
        return UI::render(RoleResource::class, 'index');
    }

    public function create()
    {
        return UI::render(RoleResource::class, 'create');
    }

    public function show(Role $role)
    {
        return UI::render(RoleResource::class, 'show', [
            'role' => $role
        ]);
    }

    public function edit(Role $role)
    {
        return UI::render(RoleResource::class, 'edit', [
            'role' => $role
        ]);
    }
}
