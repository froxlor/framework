<?php

namespace Froxlor\UI\Contracts;

use Froxlor\UI\Support\AttributeResolver;
use Illuminate\Support\Str;
use Livewire\Wireable;

abstract class ResourceComponent implements Wireable, Payloadable, Resolvable
{
    public string $key;

    public string $view;

    public ?array $actions = null;

    public ?string $description = null;

    public ?string $teaser = null;

    public ?string $title = null;

    public array $props = [];

    public array $schema = [];

    private \Closure|bool|null $redirectFirstResolver = null;

    private bool $resolved = false;

    protected array $fillable = [
        'key', 'title', 'description', 'teaser', 'actions', 'schema', 'props', 'push',
    ];

    public function __construct(string $key = 'main')
    {
        $this->key = $key;
    }

    public static function make(string $key = 'main'): static
    {
        return new static($key);
    }

    protected function serializeItem(mixed $item, array $context = []): mixed
    {
        if (method_exists($item, 'resolve')) {
            $item = $item->resolve($context);
        }

        if (method_exists($item, 'toPayload')) {
            return (object)$item->toPayload();
        }

        return $item;
    }

    protected function serializeItems(array $items, array $context = []): array
    {
        return array_map(
            fn($item) => $this->serializeItem($item, $context),
            $items
        );
    }

    public function actions(array $actions): static
    {
        $this->actions = $this->serializeItems($actions, $this->props ?? []);

        return $this;
    }

    public function description(callable|string|null $value): static
    {
        $this->description = trans($value);

        return $this;
    }

    public function teaser(callable|string|null $value): static
    {
        $this->teaser = $value;

        return $this;
    }

    public function title(callable|string|null $value): static
    {
        $this->title = trans($value);

        return $this;
    }

    public function redirectFirst(callable|bool $value): static
    {
        $this->redirectFirstResolver = $value instanceof \Closure
            ? $value
            : fn() => $value;

        return $this;
    }

    public function shouldRedirectFirst(): bool
    {
        if ($this->redirectFirstResolver === null) {
            return false;
        }

        return (bool)app()->call($this->redirectFirstResolver);
    }

    public function toLivewire(): array
    {
        $payload = $this->toPayload();

        $payload['redirectFirst'] = $this->shouldRedirectFirst();

        unset($payload['resolved'], $payload['redirect_first_resolver']);

        return $payload;
    }

    public static function fromLivewire($value): ResourceComponent
    {
        return new static($value['key'] ?? 'main')->fill($value);
    }

    protected function serializeProps(array $props): array
    {
        return collect($props)->map(function ($value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    return $value->toArray();
                }

                if (method_exists($value, 'toPayload')) {
                    return $value->toPayload();
                }

                return (string) $value;
            }

            return $value;
        })->toArray();
    }

    private function applyResolve(): void
    {
        $context = $this->props ?? [];

        $this->props = $this->serializeProps($this->props);
        $this->title = AttributeResolver::value($this->title, $context);
        $this->description = AttributeResolver::value($this->description, $context);
        $this->teaser = AttributeResolver::value($this->teaser, $context);

        if (is_array($this->actions)) {
            $this->actions = array_map(
                fn($a) => $this->serializeItem($a, $context),
                $this->actions
            );
        }

        $this->normalizeArrayPropsToObjects();
        $this->resolved = true;
    }

    public function toPayload(): array
    {
        if (!$this->resolved) {
            $this->applyResolve();
        }

        $payload = collect(get_object_vars($this))
            ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
            ->toArray();

        unset($payload['resolved'], $payload['redirect_first_resolver']);

        return $payload;
    }

    public function fill(array|object $attributes): static
    {
        $allowed = $this->fillable();

        foreach ((array)$attributes as $key => $value) {
            if (in_array($key, $allowed, true) && property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this->normalizeArrayPropsToObjects();
    }

    protected function fillable(): array
    {
        return $this->fillable;
    }

    public function normalizeArrayPropsToObjects(): static
    {
        foreach ($this->normalizableProperties() as $property) {
            if (!property_exists($this, $property) || !is_iterable($this->{$property})) {
                continue;
            }

            $this->{$property} = $this->normalizeArrayItems($this->{$property});
        }

        return $this;
    }

    protected function normalizableProperties(): array
    {
        return ['schema', 'actions', 'columns', 'columnActions'];
    }

    protected function normalizeArrayItems(iterable $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $item = is_array($item) ? (object)$item : $item;

            if (is_object($item) && isset($item->schema) && is_iterable($item->schema)) {
                $item->schema = $this->normalizeArrayItems($item->schema);
            }

            $normalized[] = $item;
        }

        return $normalized;
    }
}
