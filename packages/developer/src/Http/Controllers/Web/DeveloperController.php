<?php

namespace Froxlor\Developer\Http\Controllers\Web;

use Froxlor\Developer\Http\Controllers\Controller;
use Froxlor\UI\Support\UI;

class DeveloperController extends Controller
{
    public function index()
    {
        return view('froxlor-developer::index');
    }

    public function docs(string $folder, string $page)
    {
        return view(sprintf('froxlor-developer::docs.%s.%s', $folder, $page));
    }
}
