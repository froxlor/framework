<?php

namespace Froxlor\UI\Tables\ColumnActions;

use Froxlor\UI\Contracts\Action as BaseAction;

class Action extends BaseAction
{
    public string $view = 'ui::schema.tables.actions.column-action';

    public mixed $visible = true;

    public function visible(callable|bool $value = true): static
    {
        $this->visible = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->visible = $this->visible;

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['visible'] = $this->visible;

        return $payload;
    }
}
