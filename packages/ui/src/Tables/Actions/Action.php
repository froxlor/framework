<?php

namespace Froxlor\UI\Tables\Actions;

use Froxlor\UI\Contracts\Action as BaseAction;

class Action extends BaseAction
{
    public string $view = 'ui::schema.components.action';

    public function visible(callable|bool $value = true): static
    {
        return parent::visible($value);
    }
}
