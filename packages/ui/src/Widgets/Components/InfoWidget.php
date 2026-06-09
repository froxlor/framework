<?php

namespace Froxlor\UI\Widgets\Components;

use Froxlor\UI\Support\AttributeResolver;
use Froxlor\UI\Widgets\Widget;

class InfoWidget extends Widget
{
    public string $view = 'ui::widgets.components.info-widget';

    public mixed $value = null;

    public mixed $prefix = null;

    public mixed $suffix = null;

    public mixed $trend = null;

    public mixed $trend_tone = null;

    public function title(callable|string|null $value): static
    {
        return $this->label($value);
    }

    public function value(callable|string|int|float|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function prefix(callable|string|null $value): static
    {
        $this->prefix = $value;

        return $this;
    }

    public function suffix(callable|string|null $value): static
    {
        $this->suffix = $value;

        return $this;
    }

    public function trend(callable|string|null $value, callable|string|null $tone = null): static
    {
        $this->trend = $value;
        $this->trend_tone = $tone;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->value = AttributeResolver::value($this->value, $context);
        $clone->prefix = AttributeResolver::value($this->prefix, $context);
        $clone->suffix = AttributeResolver::value($this->suffix, $context);
        $clone->trend = AttributeResolver::value($this->trend, $context);
        $clone->trend_tone = AttributeResolver::value($this->trend_tone, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['value'] = AttributeResolver::value($this->value);
        $payload['prefix'] = AttributeResolver::value($this->prefix);
        $payload['suffix'] = AttributeResolver::value($this->suffix);
        $payload['trend'] = AttributeResolver::value($this->trend);
        $payload['trend_tone'] = AttributeResolver::value($this->trend_tone);

        return $payload;
    }
}
