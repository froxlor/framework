<?php

namespace Froxlor\UI\Forms\Components;

use Froxlor\UI\Contracts\Input;

class Color extends Input
{
    public string $view = 'ui::schema.forms.components.color';

    /** @var array<string, int> Sibling color field key => target lightness % (0-100), keeping this field's hue/saturation */
    public array $generateShadesFor = [];

    public function generateShadesFor(array $lightnessByKey): static
    {
        $this->generateShadesFor = $lightnessByKey;

        return $this;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['generateShadesFor'] = $this->generateShadesFor;

        return $payload;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->generateShadesFor = $this->generateShadesFor;

        return $clone;
    }
}
