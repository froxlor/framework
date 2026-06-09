<?php

namespace Froxlor\UI\Schemas\Components;

use Froxlor\UI\Concerns\HasComponent;
use Froxlor\UI\Contracts\Component;
use Froxlor\UI\Support\AttributeResolver;

/**
 * @property ?string $description
 * @property ?string $title
 */
class Section extends Component
{
    use HasComponent;

    public string $view = 'ui::schema.components.section';
    public ?string $title = null;
    public ?string $description = null;
    public bool $full_height = false;
    public ?string $variant = null;

    public function description($value): static
    {
        $this->description = $value;

        return $this;
    }

    public function title($value): static
    {
        $this->title = trans($value);

        return $this;
    }

    public function fullHeight(bool $value = true): static
    {
        $this->full_height = $value;

        return $this;
    }

    public function variant(callable|string|null $value): static
    {
        $this->variant = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->title = AttributeResolver::value($this->title, $context);
        $clone->description = AttributeResolver::value($this->description, $context);
        $clone->full_height = AttributeResolver::value($this->full_height, $context) ?? false;
        $clone->variant = AttributeResolver::value($this->variant, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['title'] = AttributeResolver::value($this->title);
        $payload['description'] = AttributeResolver::value($this->description);
        $payload['full_height'] = AttributeResolver::value($this->full_height) ?? false;
        $payload['variant'] = AttributeResolver::value($this->variant);

        return $payload;
    }
}
