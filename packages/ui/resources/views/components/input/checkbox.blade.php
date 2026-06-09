@props(['name' => null, 'label' => null, 'checked' => false, 'containerClass' => null])

@php
    $id = $attributes->get('id') ?? ($name ?? uniqid('checkbox_'));
    $hasLabel = filled($label) || trim((string) $slot) !== '';
@endphp

<div @class([
    'inline-flex items-center',
    'gap-3' => $hasLabel,
    $containerClass,
])>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        @checked($checked)
        {{ $attributes->except('id')->twMerge('h-4 w-4 rounded-md border-zinc-300 bg-white text-primary-600 shadow-sm transition [color-scheme:light] focus:ring-primary-500 dark:border-white/15 dark:bg-white/5 dark:[color-scheme:dark]') }}
    >

    @if(filled($label))
        <x-ui::label :for="$id">{{ $label }}</x-ui::label>
    @endif

    @if(trim((string) $slot) !== '')
        {{ $slot }}
    @endif
</div>
