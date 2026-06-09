<?php

namespace Froxlor\UI\Tables\Actions;

use Froxlor\UI\Contracts\Action as BaseAction;

class Action extends BaseAction
{
    public string $view = 'ui::schema.components.action';

    public function visible(callable|bool $value): static
    {
        if ($value instanceof \Closure) {
            $this->attributes['visible'] = app()->call($value);
            return $this;
        }

        $this->attributes['visible'] = $value;

        return $this;
    }
}
