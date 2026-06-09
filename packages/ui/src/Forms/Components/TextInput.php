<?php

namespace Froxlor\UI\Forms\Components;

use Froxlor\UI\Contracts\Input;

/**
 * @property ?string $type
 */
class TextInput extends Input
{
    public string $view = 'ui::schema.forms.components.text-input';

    public ?string $type = 'text';

    public function email(): static
    {
        $this->type = 'email';

        return $this;
    }

    public function numeric(): static
    {
        $this->type = 'numeric';

        return $this;
    }

    public function integer(): static
    {
        $this->type = 'integer';

        return $this;
    }

    public function password(): static
    {
        $this->type = 'password';

        return $this;
    }

    public function tel(): static
    {
        $this->type = 'tel';

        return $this;
    }

    public function url(): static
    {
        $this->type = 'url';

        return $this;
    }
}
