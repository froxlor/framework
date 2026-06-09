<?php

namespace Froxlor\UI\Livewire;

use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Setting;
use Froxlor\UI\Support\UI;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\View\View;
use Livewire\Component;
use TailwindMerge\Laravel\Facades\TailwindMerge;

new class extends Component
{
    public Authenticatable|User|null $user;

    public function __construct(
        public ?string $title = null,
        public ?string $navigation = null,
        public ?string $userNavigation = null,
        public ?string $class = null
    )
    {
        $this->title = config('app.name', 'froxlor');
        $this->user = auth()->user();
    }
};
?>

@inject('ui', 'Froxlor\UI\Support\UI')
@inject('tw', 'TailwindMerge\Laravel\Facades\TailwindMerge')
@inject('setting', 'Froxlor\Core\Support\Setting')

<x-ui::navbar {{ $attributes->twMerge($class) }}>
    <!-- Logo / Title -->
    <x-ui::link href="{{ route('overview') }}" class="flex items-center space-x-4 text-inherit dark:text-inherit hover:no-underline">
        <x-ui::logo class="h-8 w-auto"/>
        <span class="font-medium text-xl">{{ $title }}</span>
    </x-ui::link>

    <!-- Left Side -->
    <x-slot name="left">
        <x-ui::navigation>
            <!-- Navigation -->
            @if($navigation)
                <x-ui::navigation.list class="hidden lg:flex">
                    @foreach($ui::stack($navigation) as $item)
                        @continue(!$item->visible)
                        <x-ui::navigation.item>
                            <x-ui::navigation.link :href="$item->href" :active="$item->active" :icon="$item->icon->name" :icon-variant="$item->icon->variant">
                                {{ $item->label }}
                            </x-ui::navigation.link>
                        </x-ui::navigation.item>
                    @endforeach
                </x-ui::navigation.list>
            @endif
            <!-- User Navigation -->
            @if($userNavigation)
                <x-ui::navigation.list>
                    <x-ui::dropdown>
                        <x-slot:trigger>
                            <x-ui::avatar x-data="{ src: '{{ $user->avatar }}', fallback: '{{ $user->acronym }}' }">
                                <x-ui::avatar.image x-bind:src="src" x-show="src"/>
                                <x-ui::avatar.fallback x-text="fallback"/>
                            </x-ui::avatar>
                            <span class="hidden lg:inline-block">
                                {{ $user->name }}
                            </span>
                            <x-ui::icon name="chevron-down" class="ml-2 mt-1 h-4 w-4"/>
                        </x-slot:trigger>

                        <x-slot:content>
                            @foreach($ui::stack($userNavigation) as $key => $item)
                                @continue(!$item->visible)
                                @if($item->label && $item->icon)
                                    @if(!$loop->first)
                                        <x-ui::dropdown.divider/>
                                    @endif
                                @else
                                    <x-ui::dropdown.link :href="$item->href ?: '#'" :icon="$item->icon">
                                        {{ $item->label }}
                                    </x-ui::dropdown.link>
                                @endif
                                @foreach($item->children as $child)
                                    <x-ui::dropdown.link :href="$item->href" :icon="$child->icon">
                                        {{ $child->label }}
                                    </x-ui::dropdown.link>
                                @endforeach
                            @endforeach
                        </x-slot:content>
                    </x-ui::dropdown>
                </x-ui::navigation.list>
            @endif
            <x-ui::navigation.list class="lg:hidden">
                <x-ui::navigation.item>
                    <x-ui::sidebar.trigger name="sidebar"/>
                </x-ui::navigation.item>
            </x-ui::navigation.list>
        </x-ui::navigation>
    </x-slot>
</x-ui::navbar>
