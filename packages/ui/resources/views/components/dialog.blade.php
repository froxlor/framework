@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
    $maxWidth = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    };
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            const selector = 'a, button, input:not([type=hidden]), textarea, select, details, [tabindex]:not([tabindex=-1])';
            return [...$el.querySelectorAll(selector)].filter(el => !el.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() {
            const index = this.focusables().indexOf(document.activeElement);
            return this.focusables()[(index + 1) % this.focusables().length] || this.firstFocusable();
        },
        prevFocusable() {
            const index = this.focusables().indexOf(document.activeElement);
            return this.focusables()[(index - 1 + this.focusables().length) % this.focusables().length] || this.lastFocusable();
        }
    }"
    x-init="$watch('show', value => {
        document.body.classList.toggle('overflow-y-hidden', value);
        if (value && {{ $attributes->has('focusable') ? 'true' : 'false' }}) {
            setTimeout(() => firstFocusable()?.focus(), 100);
        }
    })"
    x-on:open-dialog.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="!$event.shiftKey && nextFocusable()?.focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable()?.focus()"
    x-show="show"
    class="fixed inset-0 z-40 flex items-center justify-center px-4 py-6 sm:px-0"
    style="display: {{ $show ? 'flex' : 'none' }};"
>

    <!-- Backdrop -->
    <div
        x-show="show"
        class="fixed inset-0 bg-gray-500 dark:bg-zinc-800 opacity-75 transition-opacity z-40"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-75"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-75"
        x-transition:leave-end="opacity-0"
        x-on:click="show = false"
    ></div>

    <!-- Modal -->
    <div
        x-show="show"
        class="relative z-50 w-full overflow-hidden rounded-xl bg-card shadow-xl transform transition-all dark:bg-zinc-950 {{ $maxWidth }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
