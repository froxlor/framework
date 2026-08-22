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

<td class="px-4 py-3 align-middle">
    @php($value = is_string($value) ? $value : json_encode($value))
    @php($valueClasses = ($column->small ?? false) ? 'text-xs text-zinc-500 dark:text-zinc-400' : null)
    @php($descriptionLines = data_get($row, '__descriptions.' . ($column->key ?? ''), []))
    @if ($url = UrlResolver::resolve($resource->intended, $row))
        <a class="block w-full h-full" wire:navigate href="{{ $url }}">
            <span class="{{ $valueClasses }}">
                @if($column->html ?? false)
                    {!! $value !!}
                @else
                    {{ $value }}
                @endif
            </span>
        </a>
    @else
        <span class="{{ $valueClasses }}">
            @if($column->html ?? false)
                {!! $value !!}
            @else
                {{ $value }}
            @endif
        </span>
    @endif
    @if(is_array($descriptionLines) && count($descriptionLines))
        <div class="mt-0.5 space-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            @foreach($descriptionLines as $line)
                <div>
                    @if($line['html'] ?? false)
                        {!! $line['value'] !!}
                    @else
                        {{ $line['value'] }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</td>
