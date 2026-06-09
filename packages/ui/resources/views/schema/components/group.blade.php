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
    $spanClass = match ((string) ($schema->col_span ?? '1')) {
        'full' => 'col-span-full',
        '2' => 'col-span-1 sm:col-span-2',
        '3' => 'col-span-1 sm:col-span-3',
        default => 'col-span-1',
    };
@endphp

<div class="grid {{ $spanClass }} gap-6">
    @include('ui::schema.partials.render-schema-items', [
        'items' => $schema->schema ?? [],
        'data' => $data,
        'resource' => $resource,
        'cols' => null,
        'gap' => 'gap-6',
        'wrapEach' => true,
    ])
</div>
