<?php

namespace Froxlor\UI\Contracts;

use Froxlor\UI\Concerns\HasCallable;
use Froxlor\UI\Contracts\Payloadable;
use Froxlor\UI\Contracts\Resolvable;
use Froxlor\UI\Support\AttributeResolver;
use Froxlor\UI\Support\RouteAttributeNormalizer;
use Laravel\SerializableClosure\SerializableClosure;

abstract class Action implements Payloadable, Resolvable
{
    use HasCallable;

    public string $key;

    public ?string $label = null;

    public ?string $href = null;

    public mixed $handler = null;

    public ?string $handlerToken = null;

    public mixed $confirm = null;

    public bool $destructive = false;

    public array|null $intended = null;

    public array|null $icon = null;

    public string $method = 'GET';

    public ?string $variant = null;

    protected string $view;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(callable|string|null $value): static
    {
        $this->label = trans($value);

        return $this;
    }

    public function href(callable|string|null $value): static
    {
        $this->href = $value;

        return $this;
    }

    /**
     * FIXME: unstable/untested, this is for mass actions
     */
    public function handler(callable|string|array|null $value): static
    {
        $this->handler = $value;

        return $this;
    }

    public function confirm(
        ?string $title = null,
        ?string $description = null,
        ?string $confirmLabel = null,
        ?string $cancelLabel = null,
    ): static
    {
        $hasCustomValues = $title !== null
            || $description !== null
            || $confirmLabel !== null
            || $cancelLabel !== null;

        $this->confirm = $hasCustomValues
            ? array_filter([
                'title' => $title,
                'description' => $description,
                'confirm_label' => $confirmLabel,
                'cancel_label' => $cancelLabel,
            ], fn (mixed $value) => $value !== null)
            : true;

        return $this;
    }

    public function destructive(bool $value = true): static
    {
        $this->destructive = $value;

        if ($value && $this->variant === null) {
            $this->variant = 'danger';
        }

        return $this->confirm();
    }

    public function intendedRoute(string $route, array $attributes): static
    {
        $this->intended = [
            'route' => $route,
            'attributes' => RouteAttributeNormalizer::normalize($attributes),
        ];

        return $this;
    }

    public function icon(callable|string $value, callable|string|null $variant = null): static
    {
        $this->icon = [
            'name' => $value,
            'variant' => $variant,
        ];

        return $this;
    }

    public function method(callable|string $value): static
    {
        $this->method = $value;

        return $this;
    }

    public function variant(callable|string|null $value): static
    {
        $this->variant = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = clone $this;
        $clone->label = AttributeResolver::value($this->label, $context);
        $clone->href = AttributeResolver::value($this->href, $context);
        $clone->variant = AttributeResolver::value($this->variant, $context);
        $clone->handler = $this->handler;
        $clone->handlerToken = $this->handlerToken;
        $confirm = $this->confirm;
        $clone->confirm = $confirm;
        $clone->destructive = $this->destructive;

        $intended = AttributeResolver::value($this->intended, $context);
        $clone->intended = is_array($intended) ? $intended : null;

        $icon = AttributeResolver::value($this->icon, $context);
        if (is_object($icon)) {
            $icon = (array)$icon;
        }
        $clone->icon = is_array($icon) ? $icon : null;

        return $clone;
    }

    public function toPayload(): array
    {
        $resolved = $this->resolve();

        return [
            'key' => $resolved->key,
            'label' => $resolved->label,
            'href' => $resolved->href,
            'handler' => $this->serializeHandler($resolved->handler),
            'handler_token' => $resolved->handlerToken,
            'confirm' => $resolved->confirm,
            'destructive' => $resolved->destructive,
            'intended' => $resolved->intended,
            'icon' => $resolved->icon,
            'method' => $resolved->method,
            'variant' => $resolved->variant,
            'view' => $this->view,
        ];
    }

    public function toObject(): object
    {
        return (object)$this->toPayload();
    }

    protected function serializeHandler(mixed $handler): mixed
    {
        if ($handler instanceof \Closure) {
            return [
                '__serialized_action_handler' => true,
                'type' => 'closure',
                'value' => serialize(new SerializableClosure($handler)),
            ];
        }

        if ((is_string($handler) && $handler !== '') || (is_array($handler) && is_callable($handler))) {
            return [
                '__serialized_action_handler' => true,
                'type' => 'callable',
                'value' => $handler,
            ];
        }

        return $handler;
    }
}
