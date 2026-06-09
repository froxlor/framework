<?php

use Froxlor\UI\Contracts\ResourceComponent;
use Froxlor\UI\Schemas\Schema as SchemaResource;
?>

@php
    $items = $items ?? [];
    $cols = $cols ?? null;
    $gap = $gap ?? 'gap-8';
    $wrapEach = $wrapEach ?? false;
    $gridCols = data_get($resource ?? null, 'grid_cols');

    $gridCols ??= match ((int) $cols) {
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
        5 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
        6 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6',
        default => 'grid-cols-1',
    };
@endphp

@if($cols)
    <div class="grid {{ $gridCols }} {{ $gap }}">
@endif

@foreach($items as $item)
    @continue(!(bool) ($item->visible ?? true))

    @php($componentResource = $item instanceof ResourceComponent ? $item : $resource)
    @php($wrapClass = (($item->full_height ?? false) ? 'h-full' : null))

    @if($wrapEach)
        <div @class([$wrapClass])>
    @endif

    @if($item instanceof SchemaResource)
        @include('ui::schema.partials.render-schema-items', [
            'items' => $item->schema ?? [],
            'data' => $data,
            'resource' => $item,
            'cols' => $item->cols ?? 1,
            'gap' => 'gap-8',
            'wrapEach' => false,
        ])
    @elseif(
        str_starts_with($item->view, 'ui::schema.forms.components.')
        || str_starts_with($item->view, 'ui::schema.components.')
        || str_starts_with($item->view, 'ui::schema.pages.components.')
        || str_starts_with($item->view, 'ui::widgets.components.')
    )
        @include($item->view, ['data' => $data, 'resource' => $componentResource, 'schema' => $item])
    @else
        @livewire($item->view, ['data' => $data, 'resource' => $componentResource, 'schema' => $item])
    @endif

    @if($wrapEach)
        </div>
    @endif
@endforeach

@if($cols)
    </div>
@endif
