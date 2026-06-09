<?php

namespace Froxlor\UI\Tables\Columns;

use Froxlor\UI\Contracts\Column;
use Froxlor\UI\Support\AttributeResolver;
use Illuminate\Support\Carbon;

class TextColumn extends Column
{

    public bool $html = false;

    public mixed $formatValue = null;

    public string $view = 'ui::schema.tables.columns.text-column';

    public function date(string $format = 'Y-m-d'): static
    {
        $this->formatValue(fn ($value) => Carbon::parse($value)->format($format));

        return $this;
    }

    public function dateTime(string $format = 'Y-m-d H:i:s'): static
    {
        $this->formatValue(fn ($value) => Carbon::parse($value)->format($format));

        return $this;
    }

    public function formatValue(callable|string|null $value): static
    {
        $this->formatValue = $value;

        return $this;
    }

    public function html(callable|bool $value = true): static
    {
        $this->html = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->html = (bool)AttributeResolver::value($this->html, $context);
        $clone->formatValue = $this->formatValue;

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['html'] = AttributeResolver::value($this->html) ?? false;
        $payload['formatValue'] = $this->formatValue;

        return $payload;
    }
}
