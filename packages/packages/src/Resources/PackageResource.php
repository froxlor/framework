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
                    ->description(fn ($row) => $row['description'] ?? null)
                    ->description(fn ($row) => self::authorAndWebsiteLine($row), html: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('version')
                    ->label(trans('froxlor-core::generic.version'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('enabled')
                    ->label(trans('froxlor-core::generic.enabled'))
                    ->trueIcon('circle-check')
                    ->falseIcon('circle-x')
                    ->trueVariant('primary')
                    ->falseVariant('secondary')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('disabled')
                    ->label(trans('froxlor-packages::generic.safe_mode'))
                    ->trueIcon('shield-alert')
                    ->falseIcon('shield-check')
                    ->trueVariant('danger')
                    ->falseVariant('primary')
                    ->toggleable(isHiddenByDefault: true),

                Tables\Columns\TextColumn::make('disabled_reason')
                    ->label(trans('froxlor-packages::generic.reason'))
                    ->formatValue(fn ($value) => $value ?? '')
                    ->toggleable(isHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pending_reason')
                    ->label(trans('froxlor-packages::generic.needs_input'))
                    ->formatValue(fn ($value) => $value ?? '')
                    ->toggleable(isHiddenByDefault: true),

                Tables\Columns\TextColumn::make('license')
                    ->label(trans('froxlor-core::generic.license'))
                    ->formatValue(fn ($value) => is_array($value) ? implode(', ', $value) : ($value ?? trans('froxlor-core::generic.none')))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TooltipColumn::make('dependant')
                    ->label(trans('froxlor-core::generic.dependant'))
                    ->formatValue(fn ($value) => self::dependencyCountLabel($value))
                    ->tooltip(fn ($value) => self::dependencyTooltip($value))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TooltipColumn::make('depends')
                    ->label(trans('froxlor-core::generic.depends'))
                    ->formatValue(fn ($value) => self::dependencyCountLabel($value))
                    ->tooltip(fn ($value) => self::dependencyTooltip($value))
                    ->sortable()
                    ->toggleable(),
            ])
            ->columnActions([
                Tables\ColumnActions\Action::make('install')
                    ->label(trans('froxlor-packages::generic.install'))
                    ->intendedRoute('packages.install', ['package' => '{id}'])
                    ->visible(fn ($row) => !($row['installed'] ?? false))
                    ->variant('primary')
                    ->method('post')
                    ->icon('plus'),

                Tables\ColumnActions\Action::make('enable')
                    ->label(trans('froxlor-packages::generic.re_enable'))
                    ->intendedRoute('packages.safe-mode.enable', ['package' => '{id}'])
                    ->visible(fn ($row) => (bool)($row['disabled'] ?? false))
                    ->variant('primary')
                    ->icon('shield-check')
                    ->method('post'),

                Tables\ColumnActions\Action::make('activate')
                    ->label(trans('froxlor-core::generic.enable'))
                    ->intendedRoute('packages.enable', ['package' => '{id}'])
                    ->visible(fn ($row) => (bool)($row['toggleable'] ?? false) && !($row['enabled'] ?? true))
                    ->variant('primary')
                    ->icon('circle-check')
                    ->method('post'),

                Tables\ColumnActions\Action::make('deactivate')
                    ->label(trans('froxlor-core::generic.disable'))
                    ->intendedRoute('packages.disable', ['package' => '{id}'])
                    ->visible(fn ($row) => (bool)($row['toggleable'] ?? false) && (bool)($row['enabled'] ?? true))
                    ->variant('secondary')
                    ->icon('circle-x')
                    ->method('post'),

                Tables\ColumnActions\Action::make('complete')
                    ->label(trans('froxlor-packages::generic.complete'))
                    ->intendedRoute('packages.complete', ['package' => '{id}'])
                    ->visible(fn ($row) => !empty($row['pending_route']))
                    ->variant('primary')
                    ->icon('circle-alert'),

                Tables\ColumnActions\Action::make('uninstall')
                    ->label(trans('froxlor-packages::generic.uninstall'))
                    ->intendedRoute('packages.uninstall', ['package' => '{id}'])
                    ->visible(fn ($row) => (bool)($row['installed'] ?? false))
                    ->disabled(fn ($row) => (bool)($row['has_dependants'] ?? false))
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

    /**
     * Normalizes a depends/dependant value (either a plain list of package names, or a
     * package => version-constraint map) into a flat list of "package:version" strings.
     */
    private static function dependencyItems(mixed $value): array
    {
        if (!is_array($value) || $value === []) {
            return [];
        }

        return array_is_list($value)
            ? $value
            : array_map(
                fn ($version, $package) => $package . ':' . $version,
                $value,
                array_keys($value)
            );
    }

    /**
     * Compact "N packages" summary shown in the cell, so a long dependency list
     * doesn't blow out the row height — the full list is shown in the tooltip.
     */
    private static function dependencyCountLabel(mixed $value): string
    {
        return trans_choice('froxlor-packages::generic.dependency_count', count(self::dependencyItems($value)));
    }

    /**
     * Full "package:version" list rendered inside the column's hover tooltip.
     */
    private static function dependencyTooltip(mixed $value): ?string
    {
        $items = self::dependencyItems($value);

        return $items !== [] ? implode('<br>', array_map('e', $items)) : null;
    }

    /**
     * Renders "by <authors>, <website>" for the small description line under a package's
     * name, linking each author to their homepage when composer.json provides one.
     */
    private static function authorAndWebsiteLine(array $row): ?string
    {
        $authors = is_array($row['authors'] ?? null) ? $row['authors'] : [];

        $authorNames = array_values(array_filter(array_map(function ($author) {
            if (!is_array($author) || empty($author['name'])) {
                return null;
            }

            $name = e($author['name']);

            return !empty($author['homepage'])
                ? '<a href="' . e($author['homepage']) . '" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">' . $name . '</a>'
                : $name;
        }, $authors)));

        $website = $row['homepage'] ?? null;
        $websiteLink = $website
            ? '<a href="' . e($website) . '" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">' . e(preg_replace('#^https?://#i', '', $website)) . '</a>'
            : null;

        $parts = array_filter([
            $authorNames !== [] ? implode(', ', $authorNames) : null,
            $websiteLink,
        ]);

        return $parts !== [] ? implode(' &middot; ', $parts) : null;
    }
}
