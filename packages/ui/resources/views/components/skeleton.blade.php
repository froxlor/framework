@props([
    'variant' => 'rect', // rect | text | circle
    'lines' => 3,       // number of lines for text variant
    'animate' => true,  // apply animate-pulse
    'width' => null,    // tailwind width class, e.g. 'w-64'
    'height' => null,   // tailwind height class, e.g. 'h-4'
    'rounded' => null,  // null | sm | md | lg | full (only for rect/text)
])

@php
    $pulse = $animate ? 'animate-pulse' : '';
    $bg = 'bg-zinc-800/60';

    $roundedRect = $rounded ? (str_starts_with($rounded, 'rounded-') ? $rounded : 'rounded-' . $rounded) : 'rounded-md';
    $roundedLine = $rounded ? (str_starts_with($rounded, 'rounded-') ? $rounded : 'rounded-' . $rounded) : 'rounded';

    $rectWidth = $width ?: 'w-48';
    $rectHeight = $height ?: 'h-3';

    $circleWidth = $width ?: 'w-10';
    $circleHeight = $height ?: 'h-10';
@endphp

@if($variant === 'text')
    <div {{ $attributes->twMerge('w-full') }} aria-hidden="true">
        <div class="space-y-2 {{ $pulse }}">
            @for($i = 1; $i <= (int) $lines; $i++)
                @php
                    $isLast = $i === (int) $lines;
                    $lineWidth = $width ?: ($isLast ? 'w-2/3' : 'w-full');
                @endphp
                <div class="{{ $lineWidth }} {{ $bg }} {{ $roundedLine }} {{ $height ?: 'h-3' }}"></div>
            @endfor
        </div>
    </div>
@elseif($variant === 'circle')
    <div {{ $attributes->twMerge("$bg $pulse rounded-full $circleWidth $circleHeight") }} aria-hidden="true"></div>
@else
    <div {{ $attributes->twMerge("$bg $pulse $roundedRect $rectWidth $rectHeight") }} aria-hidden="true"></div>
@endif
