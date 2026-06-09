@inject('ui', 'Froxlor\UI\Support\UI')

<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::heading>
            <div>
                <x-ui::title>{{ trans('froxlor-core::settings.title') }}</x-ui::title>
                <x-ui::subtitle>{{ trans('froxlor-core::settings.description') }}</x-ui::subtitle>
            </div>
            <x-slot:actions>
                <x-ui::button icon="sprout">{{ trans('froxlor-core::settings.current_mode', ['mode' => trans('froxlor-core::settings.easy')]) }}</x-ui::button>
                <x-ui::button variant="secondary" icon="file-code-2">{{ trans('froxlor-core::settings.import_export') }}</x-ui::button>
            </x-slot>
        </x-ui::heading>

        <x-ui::grid cols="6">
            @foreach($items as $item)
                <x-ui::card class="py-0 hover:bg-primary/80">
                    <x-ui::card.content class="relative !px-0">
                        <a wire:navigate href="{{ $item->href }}" class="flex flex-col space-y-4 p-6 block h-full w-full">
                            @if($item->badge)
                                <x-ui::badge class="absolute top-6 end-6" :variant="$item->badge->variant" :label="$item->badge->label"/>
                            @endif
                            <x-ui::icon color="subtle" :name="$item->icon->name" :color="$item->icon->color ?? null" :variant="$item->icon->variant ?? null" size="2"/>
                            <x-ui::lead>{{ $item->label }}</x-ui::lead>
                        </a>
                    </x-ui::card.content>
                </x-ui::card>
            @endforeach
        </x-ui::grid>
    </x-ui::main>
</x-ui::auth-layout>
