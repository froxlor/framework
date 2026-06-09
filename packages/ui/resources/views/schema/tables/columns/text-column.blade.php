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
        $this->resource = new TableResource()->fill($this->resource);
    }
}
?>

<td class="px-4 py-3">
    @php($value = is_string($value) ? $value : json_encode($value))
    @if ($url = UrlResolver::resolve($resource->intended, $row))
        <a class="block w-full h-full" wire:navigate href="{{ $url }}">
            @if($column->html ?? false)
                {!! $value !!}
            @else
                {{ $value }}
            @endif
        </a>
    @else
        @if($column->html ?? false)
            {!! $value !!}
        @else
            {{ $value }}
        @endif
    @endif
</td>
