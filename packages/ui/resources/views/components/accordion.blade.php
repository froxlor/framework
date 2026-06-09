<div {{ $attributes->merge(['x-data' => '{ open: null }']) }} {{ $attributes->twMerge('divide-y dark:divide-zinc-600') }}>
    {{ $slot }}
</div>

