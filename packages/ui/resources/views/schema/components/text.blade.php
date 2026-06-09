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
    $value = data_get($data, $schema->key, $schema->default ?? null);

    if (is_array($value)) {
        $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif (is_object($value)) {
        $value = method_exists($value, 'toJson')
            ? $value->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
@endphp

<x-ui::field :col-span="$schema->col ?? 6">
    <x-ui::label :for="$schema->key" :value="$schema->label" :required="$schema->required ?? false" />
    <div class="whitespace-pre-wrap break-words">{{ $value }}</div>
</x-ui::field>
