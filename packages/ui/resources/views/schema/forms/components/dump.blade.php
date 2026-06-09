<?php

use Livewire\Component;

new class extends Component
{
    public array $data;

    public object $resource;

    public object $schema;
}
?>

<x-ui::field :col-span="$schema->col ?? 6">
    <x-ui::code.playground>
        <x-slot:code>{!! json_encode(data_get($data, $schema->key), JSON_PRETTY_PRINT) !!}</x-slot:code>
    </x-ui::code.playground>
</x-ui::field>
