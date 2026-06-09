@props(['name' => null, 'label' => null])

@php
    $id = $attributes->get('id') ?? ($name ?? uniqid('toggle_'));
@endphp

<label for="{{ $id }}" class="inline-flex items-center space-x-3 cursor-pointer select-none">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
        {{ $attributes->except('id')->twMerge('sr-only peer') }}>

    <x-ui::icon
        name="toggle-left"
        size="1.5"
        class="text-zinc-600 dark:text-zinc-300 peer-checked:hidden transition"
    />
    <x-ui::icon
        name="toggle-right"
        size="1.5"
        class="hidden peer-checked:inline text-primary-600 transition"
    />

    @isset($label)
        <x-ui::label :for="$id" class="cursor-pointer">{{ $label }}</x-ui::label>
    @endisset

    {{ $slot }}
</label>
