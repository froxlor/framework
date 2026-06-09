@props(['title' => 'froxlor Development Kit'])

<x-ui::app-layout :title="$title">
    <livewire:ui::navigations.navbar class="bg-zinc-300/40 dark:bg-zinc-800/40 border-b border-black/20 dark:border-white/20 border-dashed" title="froxlor Development Kit" user-navigation="user"/>
    <x-ui::flex :grow="true">
        <livewire:ui::navigations.sidebar class="bg-zinc-300 dark:bg-zinc-800 lg:bg-zinc-300/40 dark:lg:bg-zinc-800/40 border-r border-black/20 dark:border-white/20 border-dashed" navigation="developer"/>
        <x-ui::flex.grow>
            <x-ui::main>
                {{ $slot }}
            </x-ui::main>
        </x-ui::flex.grow>
    </x-ui::flex>
</x-ui::app-layout>
