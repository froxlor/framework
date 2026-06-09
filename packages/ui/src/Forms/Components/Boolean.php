<?php

namespace Froxlor\UI\Forms\Components;

use Froxlor\UI\Contracts\Input;

class Boolean extends Input
{
    public string $view = 'ui::schema.forms.components.boolean';

    public ?string $type = 'ui::input.checkbox';

    public function toggle(): static
    {
        $this->type = 'ui::input.toggle';

        return $this;
    }
}
