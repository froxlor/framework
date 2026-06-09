<?php

use Livewire\Component;

new class extends Component
{
    public array $data;
    public object $resource;
    public object $schema;
};
?>

@php
    $variantClass = match ($schema->variant ?? null) {
        'info' => 'border-sky-500/30 bg-sky-50/80 dark:border-sky-400/20 dark:bg-sky-500/10',
        'success' => 'border-emerald-500/30 bg-emerald-50/80 dark:border-emerald-400/20 dark:bg-emerald-500/10',
        'warning' => 'border-amber-500/35 bg-amber-50/90 dark:border-amber-400/25 dark:bg-amber-500/10',
        'danger' => 'border-rose-500/30 bg-rose-50/80 dark:border-rose-400/20 dark:bg-rose-500/10',
        default => null,
    };
@endphp

<x-ui::card :class="[($schema->full_height ?? false) ? 'h-full' : null, $variantClass]">
    <x-ui::card.header>
        <x-ui::card.title>{{ $schema->title }}</x-ui::card.title>
        @isset($schema->description)
            <x-ui::card.description>{{ $schema->description }}</x-ui::card.description>
        @endisset
    </x-ui::card.header>
    <x-ui::card.content>
        @include('ui::schema.partials.render-schema-items', [
            'items' => $schema->schema ?? [],
            'data' => $data,
            'resource' => $resource,
            'cols' => 6,
            'gap' => 'gap-6',
            'wrapEach' => false,
        ])
    </x-ui::card.content>
</x-ui::card>
