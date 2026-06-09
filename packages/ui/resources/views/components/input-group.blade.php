

    // Extra classes for the internal default <x-ui::input /> to visually join with addons
    $inputJoin = collect([
        'flex-1 min-w-0',
        $hasLeftAddon ? 'rounded-l-none border-l-0 -ml-px' : '',
        $hasRightAddon ? 'rounded-r-none border-r-0' : '',
    ])->filter()->implode(' ');

    // Addon base styles
    $addonBase = 'inline-flex items-center px-3 text-sm text-zinc-500 bg-background dark:bg-input/30 border border-gray-300 dark:border-input';
@endphp

<div {{ $wrapperAttrs->twMerge('w-full flex items-stretch rounded-md shadow-sm') }}>
    @if($hasPrepend)
        <span class="{{ $addonBase }} rounded-l-md border-r-0">{{ $prepend }}</span>
    @elseif($hasPrependRaw)
        <div class="inline-flex items-stretch">{{ $prependRaw }}</div>
    @endif

    @if($hasCustomInput)
        <div class="flex-1 min-w-0">

