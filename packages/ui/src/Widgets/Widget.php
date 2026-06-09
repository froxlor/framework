<?php

namespace Froxlor\UI\Widgets;

use Froxlor\UI\Contracts\Input;
use Froxlor\UI\Support\AttributeResolver;

abstract class Widget extends Input
{
    public ?string $description = null;

    public array|string|null $icon = null;

    public ?string $href = null;

    public ?string $tone = null;

    public function colSpan(int|string $value): static
    {
        $this->col = (string) $value;

        return $this;
    }

    public function description(callable|string|null $value): static
    {
        $this->description = $value;

        return $this;
    }

    public function icon(callable|string|array|null $value, callable|string|null $variant = null): static
    {
        $this->icon = is_array($value) ? $value : [
            'name' => $value,
            'variant' => $variant ?? $this->tone,
        ];

        return $this;
    }

    public function tone(callable|string|null $value): static
    {
        $this->tone = $value;

        return $this;
    }

    public function href(callable|string|null $value): static
    {
        $this->href = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->description = AttributeResolver::value($this->description, $context);
        $clone->icon = AttributeResolver::value($this->icon, $context);
        $clone->href = AttributeResolver::value($this->href, $context);
        $clone->tone = AttributeResolver::value($this->tone, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['description'] = AttributeResolver::value($this->description);
        $payload['icon'] = AttributeResolver::value($this->icon);
        $payload['href'] = AttributeResolver::value($this->href);
        $payload['tone'] = AttributeResolver::value($this->tone);

        return $payload;
    }
}
