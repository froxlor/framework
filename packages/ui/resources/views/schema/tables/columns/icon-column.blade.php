<?php

use Froxlor\UI\Support\UrlResolver;
use Froxlor\UI\Tables\Table as TableResource;
use Livewire\Component;

new class extends Component {
    public mixed $column;

    public object $resource;

    public mixed $row;

    public mixed $value;

    public function boot(): void
    {
        $this->resource = (new TableResource())->fill($this->resource);
    }
}
?>

<td class="px-4 py-3">
    @php
        $isTrue = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        $iconName = $isTrue ? ($column->trueIcon ?? 'circle-check') : ($column->falseIcon ?? 'circle-x');
        $iconVariant = $isTrue ? ($column->trueVariant ?? 'success') : ($column->falseVariant ?? 'danger');
    @endphp
    @if ($url = UrlResolver::resolve($resource->intended, $row))
        <a class="block w-full h-full" wire:navigate href="{{ $url }}">
            <x-ui::icon :name="$iconName" :variant="$iconVariant"/>
        </a>
    @else
        <x-ui::icon :name="$iconName" :variant="$iconVariant"/>
    @endif
</td>
