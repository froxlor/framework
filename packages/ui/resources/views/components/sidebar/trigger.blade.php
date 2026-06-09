@props(['name'])

<div>
    <x-ui::icon name="menu" x-on:click="$dispatch('toggle-sidebar', '{{ $name }}')" />
</div>
