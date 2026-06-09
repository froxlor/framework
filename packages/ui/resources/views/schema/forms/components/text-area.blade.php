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
    <x-ui::label :for="$schema->key" :value="$schema->label" :required="$schema->required ?? false" />
    <x-ui::input.textarea
        :required="$schema->required ?? false"
        :name="$schema->key"
        rows="4"
        wire:model="data.{{ $schema->key }}"
    />
    <x-ui::input.error :messages="$errors->get($schema->key)" class="mt-2" />
</x-ui::field>
