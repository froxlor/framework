<?php

use Livewire\Component;

new class extends Component
{
    public array $data;
    public object $resource;
    public object $schema;
};
?>

@php
    $span = $schema->col ?? '1';
    $tone = $schema->tone ?? 'primary';
    $value = $schema->value ?? $schema->default ?? null;
    $href = $schema->href ?? null;

    $spanClass = match ((string) $span) {
        'full' => 'col-span-full',
        '2' => 'sm:col-span-2',
        '3' => 'sm:col-span-3',
        default => 'col-span-1',
    };

    $iconWrapClass = match ($tone) {
        'success' => 'bg-emerald-500/12 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
        'warning' => 'bg-amber-500/12 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'danger' => 'bg-rose-500/12 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
        'secondary' => 'bg-zinc-500/10 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300',
        default => 'bg-sky-500/12 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
    };

    $cardClasses = trim(implode(' ', array_filter([
        $spanClass,
        'h-full gap-4 overflow-hidden border border-zinc-200/70 bg-white/90 dark:border-white/10 dark:bg-white/[0.02]',
        $href ? 'transition hover:-translate-y-0.5 hover:shadow-md' : null,
    ])));
@endphp

@if($href)
    <a href="{{ $href }}" class="block h-full" style="color: inherit; text-decoration: none;">
@endif
<x-ui::card class="{{ $cardClasses }}">
    <x-ui::card.header class="gap-3 flex-1">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <x-ui::card.title>{{ $schema->label }}</x-ui::card.title>
                @if(!empty($schema->description))
                    <x-ui::card.description>{{ $schema->description }}</x-ui::card.description>
                @endif
            </div>

            @if(!empty($schema->icon))
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconWrapClass }}">
                    <x-ui::icon :name="$schema->icon" :variant="$tone" size="1.1" />
                </span>
            @endif
        </div>
    </x-ui::card.header>

    <x-ui::card.content class="mt-auto pt-0">
        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
            <div class="break-words text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">
                {{ ($schema->prefix ?? '') . $value . ($schema->suffix ?? '') }}
            </div>

            @if(!empty($schema->trend))
                <x-ui::badge :variant="$schema->trend_tone ?? $tone" size="sm">
                    {{ $schema->trend }}
                </x-ui::badge>
            @endif
        </div>
    </x-ui::card.content>
</x-ui::card>
@if($href)
    </a>
@endif
