<?php

namespace Froxlor\UI\Forms\Components;

use Froxlor\UI\Contracts\Input;
use Froxlor\UI\Support\AttributeResolver;

/**
 * @property ?array $options
 */
class Select extends Input
{
    public mixed $options = null;

    public string $view = 'ui::schema.forms.components.select';

    public function options(callable|array $value): static
    {
        $this->options = $value;

        return $this;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['options'] = AttributeResolver::value($this->options);

        return $payload;
    }
}
