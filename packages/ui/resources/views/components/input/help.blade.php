@props(['message'])

<div {{ $attributes->twMerge('text-sm text-zinc-600 space-y-1') }}>
    {{ $message }}
</div>
