<button {{ $attributes->twMerge('py-4 cursor-pointer') }} type="button"
    x-bind:id="(function(){ let el = $el.closest('[data-accordion-item]'); return el ? el.dataset.contentId + '-trigger' : null })()"
    x-bind:aria-expanded="(function(){ let el = $el.closest('[data-accordion-item]'); let name = el ? el.dataset.name : null; return open === name })()"
    x-bind:aria-controls="(function(){ let el = $el.closest('[data-accordion-item]'); return el ? el.dataset.contentId : null })()"
    @click="(function(){ let el = $el.closest('[data-accordion-item]'); let name = el ? el.dataset.name : null; let collapsible = el ? el.dataset.collapsible === '1' : false; if(!name) return; if(open === name){ if(collapsible) open = null } else { open = name } })()">
    {{ $slot }}
</button>
