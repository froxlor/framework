<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\Users\UserResource;
use Froxlor\UI\Support\UI;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return UI::render(UserResource::class, 'index');
    }

    public function create()
    {
        return UI::render(UserResource::class, 'create');
    }

    public function show(Request $request, User $user)
    {
        return UI::render(UserResource::class, 'show', [
            'user' => $user
        ]);
    }

    public function edit(User $user)
    {
        return UI::render(UserResource::class, 'edit', [
            'user' => $user
        ]);
    }
}
