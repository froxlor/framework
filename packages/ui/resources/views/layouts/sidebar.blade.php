@props(['bodyClasses' => '', 'bodySubClasses' => ''])

<x-ui::app-layout :body-classes="$bodyClasses" :body-sub-classes="$bodySubClasses">
    <x-ui::flex :grow="true">
        <livewire:ui::navigations.sidebar navigation="sidebar"/>
        <x-ui::flex.grow>
            {{ $slot }}
        </x-ui::flex.grow>
    </x-ui::flex>
</x-ui::app-layout>
