@props(['class' => null, 'dashed' => true])

<div {{ $attributes->twMerge('border-2 border-dashed border-zinc-600/50 text-zinc-400/60 dark:text-zinc-400/40 rounded-lg p-12 relative min-h-full') }}>
    @if($dashed)
        <svg class="absolute inset-0 w-full h-full pointer-events-none"
             xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <pattern id="diagLines"
                         width="8" height="8"
                         patternUnits="userSpaceOnUse"
                         patternTransform="rotate(45)">
                    <line x1="0" y1="0" x2="0" y2="8"
                          class="stroke-zinc-400/20" stroke-width="1"/>
                </pattern>
            </defs>

            <rect x="0" y="0" width="100%" height="100%" fill="url(#diagLines)"/>
        </svg>
    @endif

    <div class="relative z-10">
        {{ $slot }}
    </div>
</div>
