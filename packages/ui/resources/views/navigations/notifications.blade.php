<?php

namespace Froxlor\UI\Livewire;

use Froxlor\Core\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public Authenticatable|User|null $user;

    public function __construct(
        public ?string $class = null
    )
    {
        $this->user = auth()->user();
    }

    public function notifications(): Collection
    {
        if (!$this->user instanceof User) {
            return new Collection();
        }

        return $this->user->relevantNotifications()->limit(50)->get();
    }

    public function unreadCount(): int
    {
        if (!$this->user instanceof User) {
            return 0;
        }

        return $this->user->relevantNotifications()->whereNull('read_at')->count();
    }

    public function markAsRead(string $notificationId): void
    {
        if (!$this->user instanceof User) {
            return;
        }

        $this->user->relevantNotifications()
            ->whereKey($notificationId)
            ->whereNull('read_at')
            ->first()
            ?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        if (!$this->user instanceof User) {
            return;
        }

        $this->user->relevantNotifications()
            ->whereNull('read_at')
            ->get()
            ->each
            ->markAsRead();
    }
};
?>

@php
    $notifications = $this->notifications();
    $unreadCount = $this->unreadCount();
@endphp

<div
    {{ $attributes->twMerge('flex items-center', $class) }}
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
>
    <!-- Trigger -->
    <button
        type="button"
        x-on:click="open = true"
        class="relative inline-flex cursor-pointer items-center rounded-full p-1 text-zinc-500 transition-colors duration-150 ease-in-out hover:text-zinc-800 focus:outline-none dark:text-zinc-400 dark:hover:text-zinc-200"
        aria-label="{{ trans('froxlor-ui::generic.notifications') }}"
    >
        <x-ui::icon name="bell" class="h-5 w-5"/>
        @if($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold leading-none text-primary-foreground">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Backdrop -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-75"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-75"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black opacity-75 dark:bg-zinc-900"
        x-on:click="open = false"
        style="display: none;"
    ></div>

    <!-- Slide-over panel -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition-transform duration-300 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-300 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex h-dvh w-96 max-w-[calc(100vw-3rem)] flex-col border-l border-zinc-200/70 bg-card text-zinc-900 shadow-lg dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-200"
        style="display: none;"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-zinc-200/70 px-4 py-4 dark:border-white/10">
            <span class="font-medium">{{ trans('froxlor-ui::generic.notifications') }}</span>
            <button
                type="button"
                x-on:click="open = false"
                class="cursor-pointer text-zinc-500 transition-colors duration-150 ease-in-out hover:text-zinc-800 focus:outline-none dark:text-zinc-400 dark:hover:text-zinc-200"
                aria-label="{{ trans('froxlor-ui::generic.close') }}"
            >
                <x-ui::icon name="x" class="h-5 w-5"/>
            </button>
        </div>

        <!-- Notifications -->
        <div class="flex-1 overflow-y-auto">
            @forelse($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div
                    wire:key="notification-{{ $notification->id }}"
                    class="flex gap-3 border-b border-zinc-200/70 px-4 py-3 dark:border-white/10 {{ $notification->unread() ? 'bg-primary/5' : '' }}"
                >
                    <x-ui::icon :name="$data['icon'] ?? 'bell'" class="mt-0.5 h-4 w-4 shrink-0 opacity-60"/>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm {{ $notification->unread() ? 'font-semibold' : 'font-medium' }}">
                            {{ $data['title'] ?? class_basename($notification->type) }}
                        </p>
                        @if(!empty($data['message']))
                            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $data['message'] }}</p>
                        @endif
                        <div class="mt-1 flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                            @if(!empty($data['href']))
                                <x-ui::link href="{{ $data['href'] }}" class="text-xs">
                                    {{ trans('froxlor-ui::generic.notification_view') }}
                                </x-ui::link>
                            @endif
                        </div>
                    </div>
                    @if($notification->unread())
                        <button
                            type="button"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="mt-1.5 shrink-0 cursor-pointer"
                            title="{{ trans('froxlor-ui::generic.mark_as_read') }}"
                            aria-label="{{ trans('froxlor-ui::generic.mark_as_read') }}"
                        >
                            <span class="block h-2.5 w-2.5 rounded-full bg-primary"></span>
                        </button>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-zinc-400 dark:text-zinc-500">
                    <x-ui::icon name="bell-off" class="h-6 w-6"/>
                    <p class="text-sm">{{ trans('froxlor-ui::generic.no_notifications') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        @if($unreadCount > 0)
            <div class="border-t border-zinc-200/70 p-3 dark:border-white/10">
                <x-ui::button type="button" variant="outline" size="sm" class="w-full" wire:click="markAllAsRead">
                    {{ trans('froxlor-ui::generic.mark_all_as_read') }}
                </x-ui::button>
            </div>
        @endif
    </div>
</div>
