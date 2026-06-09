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
    $chartId = uniqid('chart-widget-' . ($schema->key ?? 'chart') . '-', false);
    $chartHeight = max(220, (int) ($schema->height ?? 256));
    $showSummary = $schema->show_summary ?? true;
    $href = $schema->href ?? null;
    $customOptions = is_object($schema->options ?? null)
        ? (array) $schema->options
        : (is_array($schema->options ?? null) ? $schema->options : []);
    $series = collect($schema->series ?? [])->map(function ($point, $index) {
        if (is_numeric($point)) {
            return [
                'label' => (string) ($index + 1),
                'value' => (float) $point,
            ];
        }

        if (is_object($point)) {
            $point = (array) $point;
        }

        return [
            'label' => (string) ($point['label'] ?? $point['name'] ?? ($index + 1)),
            'value' => (float) ($point['value'] ?? 0),
        ];
    })->values();

    $value = $schema->value ?? null;
    $labels = $series->pluck('label')->all();
    $values = $series->pluck('value')->all();

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

    [$borderColor, $backgroundColor] = match ($tone) {
        'success' => ['rgb(16, 185, 129)', 'rgba(16, 185, 129, 0.20)'],
        'warning' => ['rgb(245, 158, 11)', 'rgba(245, 158, 11, 0.20)'],
        'danger' => ['rgb(244, 63, 94)', 'rgba(244, 63, 94, 0.20)'],
        'secondary' => ['rgb(113, 113, 122)', 'rgba(113, 113, 122, 0.20)'],
        default => ['rgb(14, 165, 233)', 'rgba(14, 165, 233, 0.20)'],
    };

    $palette = match ($tone) {
        'success' => [
            'rgba(16, 185, 129, 0.88)',
            'rgba(5, 150, 105, 0.82)',
            'rgba(52, 211, 153, 0.78)',
            'rgba(110, 231, 183, 0.74)',
            'rgba(167, 243, 208, 0.72)',
            'rgba(209, 250, 229, 0.70)',
        ],
        'warning' => [
            'rgba(245, 158, 11, 0.88)',
            'rgba(217, 119, 6, 0.82)',
            'rgba(251, 191, 36, 0.78)',
            'rgba(252, 211, 77, 0.74)',
            'rgba(253, 230, 138, 0.72)',
            'rgba(254, 243, 199, 0.70)',
        ],
        'danger' => [
            'rgba(244, 63, 94, 0.88)',
            'rgba(225, 29, 72, 0.82)',
            'rgba(251, 113, 133, 0.78)',
            'rgba(253, 164, 175, 0.74)',
            'rgba(254, 205, 211, 0.72)',
            'rgba(255, 228, 230, 0.70)',
        ],
        'secondary' => [
            'rgba(113, 113, 122, 0.88)',
            'rgba(82, 82, 91, 0.82)',
            'rgba(161, 161, 170, 0.78)',
            'rgba(212, 212, 216, 0.74)',
            'rgba(228, 228, 231, 0.72)',
            'rgba(244, 244, 245, 0.70)',
        ],
        default => [
            'rgba(14, 165, 233, 0.88)',
            'rgba(2, 132, 199, 0.82)',
            'rgba(56, 189, 248, 0.78)',
            'rgba(125, 211, 252, 0.74)',
            'rgba(186, 230, 253, 0.72)',
            'rgba(224, 242, 254, 0.70)',
        ],
    };

    $chartType = match ($schema->chart ?? 'bar') {
        'line' => 'line',
        'doughnut' => 'doughnut',
        default => 'bar',
    };

    $dataset = [
        'label' => $schema->label,
        'data' => $values,
        'borderColor' => $chartType === 'doughnut' ? array_fill(0, count($values), 'rgba(255,255,255,0.08)') : $borderColor,
        'backgroundColor' => $chartType === 'doughnut'
            ? collect(range(0, max(0, count($values) - 1)))->map(fn($index) => $palette[$index % count($palette)])->all()
            : ($chartType === 'line' ? $backgroundColor : array_fill(0, count($values), $backgroundColor)),
        'fill' => $chartType === 'line',
        'tension' => 0.35,
        'pointRadius' => $chartType === 'line' ? 3 : 0,
        'pointHoverRadius' => $chartType === 'line' ? 5 : 0,
        'borderWidth' => $chartType === 'doughnut' ? 2 : 2,
        'maxBarThickness' => 36,
        'borderRadius' => $chartType === 'bar' ? 10 : 0,
        'cutout' => $chartType === 'doughnut' ? '68%' : null,
        'hoverOffset' => $chartType === 'doughnut' ? 6 : null,
    ];

    $chartConfig = [
        'type' => $chartType,
        'data' => [
            'labels' => $labels,
            'datasets' => [$dataset],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => $chartType === 'doughnut',
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                        'color' => '#71717a',
                    ],
                ],
                'tooltip' => [
                    'displayColors' => false,
                    'backgroundColor' => 'rgba(24, 24, 27, 0.94)',
                    'borderColor' => 'rgba(255, 255, 255, 0.08)',
                    'borderWidth' => 1,
                    'padding' => 12,
                    'titleColor' => '#fafafa',
                    'bodyColor' => '#e4e4e7',
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => $chartType !== 'doughnut',
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => 'rgba(113, 113, 122, 0.9)',
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => $chartType !== 'doughnut',
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'color' => 'rgba(113, 113, 122, 0.9)',
                    ],
                    'grid' => [
                        'color' => 'rgba(161, 161, 170, 0.16)',
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
            ],
        ],
    ];

    if ($customOptions) {
        $chartConfig['options'] = array_replace_recursive($chartConfig['options'], $customOptions);
    }

    $cardClasses = trim(implode(' ', array_filter([
        $spanClass,
        'gap-4 overflow-hidden border border-zinc-200/70 bg-white/90 dark:border-white/10 dark:bg-white/[0.02]',
        $href ? 'transition hover:-translate-y-0.5 hover:shadow-md' : null,
    ])));
@endphp

@if($href)
    <a href="{{ $href }}" class="block" style="color: inherit; text-decoration: none;">
@endif
<x-ui::card class="{{ $cardClasses }}">
    <x-ui::card.header class="gap-3">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <x-ui::card.title>{{ $schema->label }}</x-ui::card.title>
                @if(!empty($schema->description))
                    <x-ui::card.description>{{ $schema->description }}</x-ui::card.description>
                @endif
            </div>

            @if(!empty($schema->icon))
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $iconWrapClass }}">
                    <x-ui::icon :name="$schema->icon" :variant="$tone" size="1.35" />
                </span>
            @endif
        </div>

        @if($value !== null)
            <div class="break-words text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">
                {{ ($schema->prefix ?? '') . $value . ($schema->suffix ?? '') }}
            </div>
        @endif
    </x-ui::card.header>

    <x-ui::card.content class="pt-0">
        @if($series->isEmpty())
            <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-10 text-center text-sm text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                {{ trans('froxlor-core::generic.no_entries') }}
            </div>
        @else
            <div class="space-y-4">
                <div data-chart-root class="rounded-2xl border border-zinc-200/80 bg-gradient-to-b from-zinc-50 to-white p-4 dark:border-white/10 dark:from-white/[0.04] dark:to-white/[0.01]">
                    <div style="height: {{ $chartHeight }}px;">
                        <canvas
                            data-chart-widget
                            data-chart-id="{{ $chartId }}"
                            role="img"
                            aria-label="{{ $schema->label }}"
                        ></canvas>
                    </div>
                    <script type="application/json" data-chart-config>@json($chartConfig)</script>
                </div>

                @if($showSummary)
                    <div class="flex flex-wrap items-start gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                        @foreach($series as $point)
                            <div class="min-w-[3.5rem] flex-1 space-y-1 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-white/[0.03]">
                                <div class="truncate">{{ $point['label'] }}</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $point['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if(!empty($schema->footer))
            <div class="border-t border-zinc-200/70 pt-4 text-sm text-zinc-500 dark:border-white/10 dark:text-zinc-400">{{ $schema->footer }}</div>
        @endif
    </x-ui::card.content>
</x-ui::card>
@if($href)
    </a>
@endif
