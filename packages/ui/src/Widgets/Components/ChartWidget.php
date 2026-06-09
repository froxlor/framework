<?php

namespace Froxlor\UI\Widgets\Components;

use Froxlor\UI\Support\AttributeResolver;
use Froxlor\UI\Widgets\Widget;

class ChartWidget extends Widget
{
    public string $view = 'ui::widgets.components.chart-widget';

    public mixed $value = null;

    public ?string $prefix = null;

    public ?string $suffix = null;

    public ?string $chart = 'bar';

    public mixed $series = [];

    public ?string $footer = null;

    public ?int $height = null;

    public bool $show_summary = true;

    public mixed $options = [];

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

    public function chart(callable|string|null $value): static
    {
        $this->chart = $value;

        return $this;
    }

    public function series(callable|array $value): static
    {
        $this->series = $value;

        return $this;
    }

    public function footer(callable|string|null $value): static
    {
        $this->footer = $value;

        return $this;
    }

    public function height(callable|int|null $value): static
    {
        $this->height = $value;

        return $this;
    }

    public function showSummary(callable|bool $value = true): static
    {
        $this->show_summary = $value;

        return $this;
    }

    public function options(callable|array $value): static
    {
        $this->options = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->value = AttributeResolver::value($this->value, $context);
        $clone->prefix = AttributeResolver::value($this->prefix, $context);
        $clone->suffix = AttributeResolver::value($this->suffix, $context);
        $clone->chart = AttributeResolver::value($this->chart, $context);
        $clone->series = AttributeResolver::value($this->series, $context) ?? [];
        $clone->footer = AttributeResolver::value($this->footer, $context);
        $clone->height = AttributeResolver::value($this->height, $context);
        $clone->show_summary = AttributeResolver::value($this->show_summary, $context) ?? true;
        $clone->options = AttributeResolver::value($this->options, $context) ?? [];

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['value'] = AttributeResolver::value($this->value);
        $payload['prefix'] = AttributeResolver::value($this->prefix);
        $payload['suffix'] = AttributeResolver::value($this->suffix);
        $payload['chart'] = AttributeResolver::value($this->chart);
        $payload['series'] = AttributeResolver::value($this->series) ?? [];
        $payload['footer'] = AttributeResolver::value($this->footer);
        $payload['height'] = AttributeResolver::value($this->height);
        $payload['show_summary'] = AttributeResolver::value($this->show_summary) ?? true;
        $payload['options'] = AttributeResolver::value($this->options) ?? [];

        return $payload;
    }
}
