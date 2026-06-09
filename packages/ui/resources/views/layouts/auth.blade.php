@props(['bodyClasses' => '', 'bodySubClasses' => ''])

<x-ui::app-layout :body-classes="$bodyClasses" :body-sub-classes="$bodySubClasses">
    <livewire:ui::navigations.navbar navigation="primary" user-navigation="user"/>
    @include('ui::navigations.mobile-sub-sidebar')
    <x-ui::flex :grow="true" class="min-h-0">
        <livewire:ui::navigations.sidebar class="lg:relative lg:z-30" navigation="sidebar" navigation-footer="sidebar-footer" :tenant-navigation="true" :collapsible="true"/>
        <livewire:ui::navigations.sidebar class="hidden border-l border-r-0 border-zinc-200/70 bg-card/80 lg:relative lg:z-10 lg:flex dark:border-white/10 dark:bg-white/[0.03]" navigation="sub-sidebar" :auto-hide="true"/>
        <x-ui::flex.grow class="min-h-0">
            {{ $slot }}
        </x-ui::flex.grow>
    </x-ui::flex>
</x-ui::app-layout>
