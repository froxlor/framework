<?php

namespace Froxlor\UI\Tables\ColumnActions;

use Froxlor\UI\Contracts\Action as BaseAction;

class Action extends BaseAction
{
    public string $view = 'ui::schema.tables.actions.column-action';

    public mixed $visible = true;

    public mixed $disabled = false;

    public function visible(callable|bool $value = true): static
    {
        $this->visible = $value;

        return $this;
    }

    public function disabled(callable|bool $value = true): static
    {
        $this->disabled = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->visible = $this->visible;
        $clone->disabled = $this->disabled;

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['visible'] = $this->visible;
        $payload['disabled'] = $this->disabled;

        return $payload;
    }
}
