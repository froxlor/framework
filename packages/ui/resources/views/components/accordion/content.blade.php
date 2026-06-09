<div
    {{ $attributes->twMerge('pb-4 overflow-hidden') }}
    x-bind:id="(function(){ let el = $el.closest('[data-accordion-item]'); return el ? el.dataset.contentId : null })()"
    x-bind:aria-labelledby="(function(){ let el = $el.closest('[data-accordion-item]'); return el ? el.dataset.contentId + '-trigger' : null })()"
    x-show="(function(){ let el = $el.closest('[data-accordion-item]'); let name = el ? el.dataset.name : null; return name ? open === name : false })()"
    x-cloak
    x-transition:enter="transition-all ease-out duration-300"
    x-transition:enter-start="max-h-0 opacity-0"
    x-transition:enter-end="max-h-screen opacity-100"
    x-transition:leave="transition-all ease-in duration-200"
    x-transition:leave-start="max-h-screen opacity-100"
    x-transition:leave-end="max-h-0 opacity-0"
>
    {{ $slot }}
</div>
