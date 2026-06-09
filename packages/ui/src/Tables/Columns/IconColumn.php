<?php

namespace Froxlor\UI\Tables\Columns;

use Froxlor\UI\Contracts\Column;
use Froxlor\UI\Support\AttributeResolver;

class IconColumn extends Column
{
    public string $view = 'ui::schema.tables.columns.icon-column';

    public mixed $trueIcon = 'circle-check';

    public mixed $falseIcon = 'circle-x';

    public mixed $trueVariant = 'primary';

    public mixed $falseVariant = 'secondary';

    public function trueIcon(callable|string $icon): self
    {
        $this->trueIcon = $icon;

        return $this;
    }

    public function falseIcon(callable|string $icon): self
    {
        $this->falseIcon = $icon;

        return $this;
    }

    public function trueVariant(callable|string $variant): self
    {
        $this->trueVariant = $variant;

        return $this;
    }

    public function falseVariant(callable|string $variant): self
    {
        $this->falseVariant = $variant;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->trueIcon = AttributeResolver::value($this->trueIcon, $context);
        $clone->falseIcon = AttributeResolver::value($this->falseIcon, $context);
        $clone->trueVariant = AttributeResolver::value($this->trueVariant, $context);
        $clone->falseVariant = AttributeResolver::value($this->falseVariant, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['trueIcon'] = AttributeResolver::value($this->trueIcon);
        $payload['falseIcon'] = AttributeResolver::value($this->falseIcon);
        $payload['trueVariant'] = AttributeResolver::value($this->trueVariant);
        $payload['falseVariant'] = AttributeResolver::value($this->falseVariant);

        return $payload;
    }
}
