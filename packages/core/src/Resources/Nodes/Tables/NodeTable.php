<?php

namespace Froxlor\Core\Resources\Nodes\Tables;

use Carbon\Carbon;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Local;
use Froxlor\UI\Tables;
use Illuminate\Support\Facades\Blade;

class
NodeTable
{

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('hostname')
                ->label(trans('froxlor-core::generic.hostname'))
                ->sortable(),

            Tables\Columns\TextColumn::make('properties.cpu.utilized')
                ->label(trans('froxlor-core::generic.cpu_usage'))
                ->formatValue(fn ($value) => Blade::render(sprintf('<x-ui::progress class="w-48" value="%s"/>', $value ?: 0)))
                ->html(),

            Tables\Columns\TextColumn::make('properties.memory.utilized')
                ->label(trans('froxlor-core::generic.memory_usage'))
                ->formatValue(fn ($value) => Blade::render(sprintf('<x-ui::progress class="w-48" value="%s" variant="amber"/>', $value ?: 0)))
                ->html(),

            Tables\Columns\TextColumn::make('properties.disk.utilized')
                ->label(trans('froxlor-core::generic.disk_usage'))
                ->formatValue(fn ($value) => Blade::render(sprintf('<x-ui::progress class="w-48" value="%s" variant="purple"/>', $value ?: 0)))
                ->html(),

            Tables\Columns\TextColumn::make('username')
                ->label(trans('froxlor-core::generic.username'))
                ->toggleable(isHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('sudo')
                ->label(trans('froxlor-core::generic.sudo'))
                ->toggleable(isHiddenByDefault: true)
                ->boolean(),

            Tables\Columns\TextColumn::make('environments_count')
                ->label(trans('froxlor-core::generic.environments')),

            Tables\Columns\TextColumn::make('tenants_count')
                ->label(trans('froxlor-core::generic.tenants'))
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->formatValue(fn ($value) => Carbon::parse($value)->format('Y-m-d'))
                ->sortable(),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('resources.nodes.create'))
                ->visible(fn() => Node::adapters() !== [Local::class])
                ->icon('plus'),
        ];
    }
}
