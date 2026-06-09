<?php

use Livewire\Component;

new class extends Component
{
    public array $data;

    public object $resource;

    public object $schema;
}
?>

@php
    $sortedSchema = collect($schema->schema ?? [])
        ->sortBy(function ($tabSchema) {
            return [
                is_null($tabSchema->sort ?? null) ? -1 : $tabSchema->sort,
                $tabSchema->label ?? '',
            ];
        })
        ->values();

    $defaultTab = $schema->default ?? ($sortedSchema->first()->key ?? null);
@endphp

@php
    $listClass = 'mb-4';

    if ($schema->overhang ?? true) {
        $listClass .= ' -mx-4 px-4 md:-mx-12 md:px-12';
    }
@endphp

<x-ui::tabs x-data="{ open: '{{ $defaultTab }}' }">
    <x-ui::tabs.list :class="$listClass">
        @foreach($sortedSchema as $tabSchema)
            <x-ui::tabs.trigger :name="$tabSchema->key">{{ $tabSchema->label }}</x-ui::tabs.trigger>
        @endforeach
    </x-ui::tabs.list>

    @foreach($sortedSchema as $tabSchema)
        @livewire($tabSchema->view, ['data' => $data, 'resource' => $resource, 'schema' => $tabSchema])
    @endforeach
</x-ui::tabs>
