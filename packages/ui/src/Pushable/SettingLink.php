<?php

namespace Froxlor\UI\Pushable;

use Froxlor\UI\Contracts\Pushable;

/**
 * @property ?array $badge
 * @property ?array $icon
 * @property ?string $label
 * @property ?string $href
 * @property ?bool $visible
 */
class SettingLink extends Pushable
{
    public array $requiredKeys = ['label', 'href', 'icon'];
    public mixed $badge = null;
    public mixed $icon = null;
    public mixed $label = null;
    public mixed $href = null;
    public mixed $visible = true;

    public function badge(callable|string|null $label, callable|string|null $variant = null): static
    {
        $this->badge = $label ? (object)[
            'label' => $this->call($label),
            'variant' => $this->call($variant),
        ] : null;

        return $this;
    }

    public function icon(callable|string|null $name, callable|string|null $variant = null): static
    {
        $this->icon = $name ? (object)[
            'name' => $this->call($name),
            'variant' => $this->call($variant)
        ] : null;

        return $this;
    }

    public function label(callable|string $value): static
    {
        $this->label = $value;

        return $this;
    }

    public function route(callable|string $value): static
    {
        $this->href = $value;

        return $this;
    }

    public function visible(callable|bool $value): static
    {
        $this->visible = $value;

        return $this;
    }
}
