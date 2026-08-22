<?php

use Froxlor\UI\Support\UrlResolver;
use Froxlor\UI\Tables\Table as TableResource;
use Livewire\Component;

new class extends Component
{
    public mixed $action;

    public bool $navigate = false;

    public object $resource;

    public mixed $row;

    public object $schema;

    public function boot(): void
    {
        $this->resource = new TableResource()->fill($this->resource);
    }
}
?>

@php
    $isVisible = data_get($row, '__visible_actions.' . ($action->key ?? ''), $action->visible ?? true);
    $isDisabled = data_get($row, '__disabled_actions.' . ($action->key ?? ''), $action->disabled ?? false);
    $method = strtoupper((string) ($action->method ?? 'GET'));
    $url = UrlResolver::resolve($action->intended, $row);
@endphp

<span>
    @if($isVisible)
        @php($iconName = is_object($action->icon) ? ($action->icon->name ?? null) : (is_array($action->icon) ? ($action->icon['name'] ?? null) : $action->icon))
        @if($isDisabled)
            {{-- Rendered as a plain, non-navigable button (never as="a" or wrapped in a form) so the
                 native `disabled` attribute actually blocks interaction — anchors ignore it. --}}
            <x-ui::button
                type="button"
                disabled
                :icon="$iconName"
                :variant="$action->variant ?? 'ghost'"
                size="xs"
            >
                {{ $action->label ?? null }}
            </x-ui::button>
        @elseif($method === 'GET')
            <x-ui::button
                as="a"
                :href="$url ?? '#'"
                :icon="$iconName"
                :variant="$action->variant ?? 'ghost'"
                size="xs"
            >
                {{ $action->label ?? null }}
            </x-ui::button>
        @else
            <form method="post" action="{{ $url }}" class="inline-flex">
                @csrf
                @method($action->method)
                <x-ui::button :icon="$iconName" :variant="$action->variant ?? 'ghost'" size="xs">
                    {{ $action->label ?? null }}
                </x-ui::button>
            </form>
        @endif
    @endif
</span>
