<?php

namespace Froxlor\Core\Http\Controllers\Api\Role;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request, Role $role)
    {
        Gate::authorize('usersViewAny', $role);

        return Response::jsonResourceCollection($role->users());
    }
}
