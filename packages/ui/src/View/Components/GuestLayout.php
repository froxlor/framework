<?php

namespace Froxlor\UI\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public function __construct(public string|array|null $bodyClasses = null, public string|array|null $bodySubClasses = null)
    {
        //
    }

    public function render(): View
    {
        return view('ui::layouts.guest');
    }
}
