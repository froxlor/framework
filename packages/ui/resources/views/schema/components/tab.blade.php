<?php

use Livewire\Component;

new class extends Component {
    public array $data;

    public object $resource;

    public object $schema;
}
?>

<x-ui::tabs.content :name="$schema->key">
    @include('ui::schema.partials.render-schema-items', [
        'items' => $schema->schema ?? [],
        'data' => $data,
        'resource' => $resource,
        'cols' => null,
        'gap' => 'gap-8',
        'wrapEach' => false,
    ])
</x-ui::tabs.content>
