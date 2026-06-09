<x-ui::app-layout :body-classes="$bodyClasses" :body-sub-classes="$bodySubClasses">
    <x-ui::middle>
        <x-slot name="before">
            <x-ui::logo class="h-20 w-auto"/>
        </x-slot>

        <x-ui::card>
            <x-ui::card.content>
                {{ $slot }}
            </x-ui::card.content>
        </x-ui::card>
    </x-ui::middle>
</x-ui::app-layout>
