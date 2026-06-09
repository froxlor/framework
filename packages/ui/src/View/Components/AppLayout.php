<?php

namespace Froxlor\UI\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public ?string $title;

    public function __construct(?string $title = null, public string|array|null $bodyClasses = null, public string|array|null $bodySubClasses = null)
    {
        $name = config('app.name', 'froxlor');

        $this->title = $title ? sprintf('%s - %s', $title, $name) : $name;
    }

    public function render(): View
    {
        return view('ui::layouts.app');
    }
}
