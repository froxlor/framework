<?php

namespace Froxlor\UI\Schemas\Components;

use Froxlor\UI\Concerns\HasComponent;
use Froxlor\UI\Concerns\HasProps;
use Froxlor\UI\Concerns\HasSort;
use Froxlor\UI\Contracts\Input;

class Tab extends Input
{
    use HasComponent, HasProps, HasSort;

    public string $view = 'ui::schema.components.tab';
}
