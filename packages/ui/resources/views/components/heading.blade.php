<div {{ $attributes->twMerge(['flex items-center justify-between']) }}>
    {{ $slot }}

    @isset($actions)
        <div class="flex items-center gap-4">
            {{ $actions }}
        </div>
    @endisset
</div>
