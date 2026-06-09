<?php

namespace Froxlor\Packages\Resources;

use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class PackageResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-packages::generic.installed_packages'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-packages::generic.installed_packages')]))
            ->fetch(route('api.packages.index'))
            ->intendedRoute('packages.edit', ['package' => '{id}'])
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
            ])
            ->columnActions([
                Tables\ColumnActions\Action::make('install')
                    ->label(trans('froxlor-packages::generic.install'))
                    ->intendedRoute('packages.install', ['package' => '{id}'])
                    ->visible(fn ($row) => !($row['installed'] ?? false))
                    ->variant('primary')
                    ->method('post')
                    ->icon('plus'),

                Tables\ColumnActions\Action::make('uninstall')
                    ->label(trans('froxlor-packages::generic.uninstall'))
                    ->intendedRoute('packages.uninstall', ['package' => '{id}'])
                    ->visible(fn ($row) => (bool)($row['installed'] ?? false))
                    ->variant('danger')
                    ->icon('trash'),
            ])
            ->actions([
                Tables\Actions\Action::make('upgrade')
                    ->label(trans('froxlor-packages::generic.upgrade_packages'))
                    ->href(route('packages.packages.upgrade'))
                    ->variant('secondary')
                    ->icon('arrow-down-to-line')
                    ->method('post'),

                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-packages::generic.install'))
                    ->href(route('packages.create'))
                    ->icon('plus'),
            ]);
    }

    public function create(?string $package = null): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-packages::generic.install'))
            ->description(trans('froxlor-packages::generic.package_help'))
            ->push(route('api.packages.store'))
            ->intendedRoute('packages.index')
            ->components([
                Section::make('section_a')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        \Froxlor\UI\Forms\Components\TextInput::make('package')
                            ->label(trans('froxlor-core::generic.name'))
                            ->default($package)
                            ->required(),
                    ]),
            ])
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('packages.index')),
            ]);
    }

    public function edit(string $package): Schema
    {
        return $this->create()
            ->fetch(route('api.packages.show', $package))
            ->push(route('api.packages.update', $package), 'PUT')
            ->components([
                Section::make('section_a')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        \Froxlor\UI\Forms\Components\TextInput::make('package')
                            ->label(trans('froxlor-core::generic.name'))
                            ->default(str_replace(':', '/', $package))
                            ->required()
                    ])
            ]);
    }

    public function uninstall(string $package): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-packages::generic.uninstall'))
            ->description(trans('froxlor-packages::generic.package_help'))
            ->push(route('api.packages.destroy', $package), 'DELETE')
            ->intendedRoute('packages.index')
            ->components([
                Section::make('section_a')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        \Froxlor\UI\Forms\Components\TextInput::make('package')
                            ->label(trans('froxlor-core::generic.name'))
                            ->default(str_replace(':', '/', $package))
                            ->required(),
                    ]),
            ])
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('packages.index')),
            ]);
    }

    public function updater(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-packages::generic.updater'))
            ->description(trans('froxlor-packages::generic.updater_description'))
            ->push(route('api.packages.update', ['package' => '*']), 'PUT')
            ->intendedRoute('packages.index')
            ->components([
                ///
            ])
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('packages.index')),
            ]);
    }
}
