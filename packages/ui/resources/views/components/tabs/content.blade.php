@props(['name'])

<div {{ $attributes->merge(['x-show' => "open === '{$name}'", 'x-cloak' => ''])->twMerge('py-6') }}>
    {{ $slot }}
</div>
