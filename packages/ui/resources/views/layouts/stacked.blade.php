@props(['bodyClasses' => '', 'bodySubClasses' => ''])

<x-ui::app-layout :body-classes="$bodyClasses" :body-sub-classes="$bodySubClasses">
    <livewire:ui::navigations.navbar navigation="primary" user-navigation="user"/>
    <x-ui::flex :grow="true">
        <x-ui::flex.grow>
            {{ $slot }}
        </x-ui::flex.grow>
    </x-ui::flex>
</x-ui::app-layout>
