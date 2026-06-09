@props([
    'paginator' => null,
    'current' => null,
    'total' => null, // total pages (only for manual mode)
    'pageName' => 'page',
    'surround' => 1, // how many pages to show around current
])

@php
    // Normalize inputs for both modes (Laravel Paginator vs manual props)
    $isLaravelPaginator = is_object($paginator) && method_exists($paginator, 'currentPage');

    $currentPage = $isLaravelPaginator
        ? (int) $paginator->currentPage()
        : (int) ($current ?? 1);

    $lastPage = $isLaravelPaginator && method_exists($paginator, 'lastPage')
        ? (int) $paginator->lastPage()
        : (int) ($total ?? 1);

    $hasPages = $isLaravelPaginator
        ? (method_exists($paginator, 'hasPages') ? $paginator->hasPages() : ($lastPage > 1))
        : ($lastPage > 1);

    $prevUrl = $isLaravelPaginator ? $paginator->previousPageUrl() : null;
    $nextUrl = $isLaravelPaginator ? $paginator->nextPageUrl() : null;

    $urlFor = function (int $page) use ($isLaravelPaginator, $paginator, $pageName) {
        if ($isLaravelPaginator) {
            return $paginator->url($page);
        }

        $baseUrl = request()->url();
        $query = request()->query();
        $query[$pageName] = $page;
        $qs = http_build_query($query);
        return $qs ? ($baseUrl.'?'.$qs) : $baseUrl;
    };

    // Build windowed page list with ellipses
    $pages = [];
    if ($lastPage <= (2 + ($surround * 2))) {
        for ($i = 1; $i <= $lastPage; $i++) $pages[] = $i;
    } else {
        $start = max(1, $currentPage - (int)$surround);
        $end   = min($lastPage, $currentPage + (int)$surround);

        // Always include first and last
        $pages[] = 1;
        if ($start > 2) $pages[] = '…';
        for ($i = $start; $i <= $end; $i++) {
            if ($i !== 1 && $i !== $lastPage) $pages[] = $i;
        }
        if ($end < $lastPage - 1) $pages[] = '…';
        if ($lastPage > 1) $pages[] = $lastPage;
    }
@endphp

@if($hasPages)
<nav {{ $attributes->twMerge('inline-flex items-center gap-1 select-none') }} role="navigation" aria-label="Pagination">
    {{-- Previous --}}
    @php
        $prevDisabled = $currentPage <= 1;
        $prevHref = $prevDisabled ? '#' : ($prevUrl ?? $urlFor(max(1, $currentPage - 1)));
    @endphp
    <a wire:navigate href="{{ $prevHref }}" rel="prev" aria-label="Previous page"
       @class([
           'inline-flex items-center justify-center rounded-md border text-sm h-9 min-w-9 px-2 outline-none',
           'bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50' => !$prevDisabled,
           'opacity-50 pointer-events-none bg-background dark:bg-input/30 dark:border-input' => $prevDisabled,
       ])>
        <x-ui::icon name="chevron-left" />
        <span class="sr-only">Prev</span>
    </a>

    {{-- Pages --}}
    @foreach($pages as $p)
        @if($p === '…')
            <span class="px-2 text-zinc-500 dark:text-zinc-400">…</span>
        @else
            @php
                $isActive = ((int)$p) === $currentPage;
                $href = $urlFor((int)$p);
            @endphp
            <a wire:navigate href="{{ $href }}" aria-label="Page {{ $p }}" aria-current="{{ $isActive ? 'page' : 'false' }}"
               @class([
                   'inline-flex items-center justify-center rounded-md border text-sm h-9 min-w-9 px-3 outline-none',
                   'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 border-primary' => $isActive,
                   'bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50' => !$isActive,
               ])>
                {{ $p }}
            </a>
        @endif
    @endforeach

    {{-- Next --}}
    @php
        $nextDisabled = $currentPage >= $lastPage || $lastPage <= 0;
        $nextHref = $nextDisabled ? '#' : ($nextUrl ?? $urlFor(min($lastPage, $currentPage + 1)));
    @endphp
    <a wire:navigate href="{{ $nextHref }}" rel="next" aria-label="Next page"
       @class([
           'inline-flex items-center justify-center rounded-md border text-sm h-9 min-w-9 px-2 outline-none',
           'bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50' => !$nextDisabled,
           'opacity-50 pointer-events-none bg-background dark:bg-input/30 dark:border-input' => $nextDisabled,
       ])>
        <x-ui::icon name="chevron-right" />
        <span class="sr-only">Next</span>
    </a>
</nav>
@endif
