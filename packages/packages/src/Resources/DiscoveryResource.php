<?php

namespace Froxlor\Packages\Resources;

use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class DiscoveryResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-packages::generic.discovery'))
            ->description(trans('froxlor-packages::generic.discovery_description'))
            ->fetch(route('api.discovery.index'))
            ->intendedRoute('packages.create', ['package' => '{id}'])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('froxlor-core::generic.name'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('version')
                    ->label(trans('froxlor-core::generic.version'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(trans('froxlor-core::generic.description'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('authors')
                    ->label(trans('froxlor-core::generic.authors'))
                    ->formatValue(function ($value) {
                        if (!is_array($value)) {
                            return $value ?? '';
                        }

                        return implode(', ', array_filter(array_map(function ($author) {
                            if (!is_array($author) || !isset($author['name'])) {
                                return null;
                            }

                            return $author['name'];
                        }, $value)));
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('license')
                    ->label(trans('froxlor-core::generic.license'))
                    ->formatValue(fn ($value) => is_array($value) ? implode(', ', $value) : ($value ?? trans('froxlor-core::generic.none')))
                    ->sortable(),

                Tables\Columns\TextColumn::make('dependant')
                    ->label(trans('froxlor-core::generic.dependant'))
                    ->formatValue(fn ($value) => is_array($value)
                        ? implode('<br>', array_is_list($value)
                            ? $value
                            : array_map(
                                fn ($version, $package) => $package . ':' . $version,
                                $value,
                                array_keys($value)
                            ))
                        : ($value ?? trans('froxlor-core::generic.none')))
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('depends')
                    ->label(trans('froxlor-core::generic.depends'))
                    ->formatValue(fn ($value) => is_array($value)
                        ? implode('<br>', array_is_list($value)
                            ? $value
                            : array_map(
                                fn ($version, $package) => $package . ':' . $version,
                                $value,
                                array_keys($value)
                            ))
                        : ($value ?? trans('froxlor-core::generic.none')))
                    ->html()
                    ->sortable(),

                Tables\Columns\IconColumn::make('installed')
                    ->label(trans('froxlor-core::generic.installed'))
                    ->sortable(),
            ])
            ->columnActions([
                Tables\ColumnActions\Action::make('install')
                    ->label(trans('froxlor-packages::generic.install'))
                    ->intendedRoute('packages.install', ['package' => '{id}'])
                    ->visible(fn($row) => !$row['installed'])
                    ->variant('primary')
                    ->method('post')
                    ->icon('plus'),

                Tables\ColumnActions\Action::make('uninstall')
                    ->label(trans('froxlor-packages::generic.uninstall'))
                    ->intendedRoute('packages.uninstall', ['package' => '{id}'])
                    ->visible(fn($row) => $row['installed'])
                    ->variant('danger')
                    ->icon('trash'),
            ]);
    }
}
