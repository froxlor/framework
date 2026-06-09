<?php

namespace Froxlor\UI\Resources;

use Froxlor\UI\Forms\Components\Color;
use Froxlor\UI\Forms\Components\Select;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Schemas\Schema;

class AppearanceResource extends Resource
{
    public function index(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-ui::generic.appearance_settings'))
            ->description(trans('froxlor-ui::generic.appearance_settings_description'))
            ->fetch(route('api.ui.appearance.index'))
            ->push(route('api.ui.appearance.store'))
            ->components([
                Section::make('main')
                    ->title(trans('froxlor-ui::generic.appearance_settings'))
                    ->description(trans('froxlor-ui::generic.appearance_settings_section_description'))
                    ->components([
                        Select::make('theme')
                            ->label(trans('froxlor-ui::generic.theme'))
                            ->options([
                                'light' => trans('froxlor-ui::generic.light'),
                                'dark' => trans('froxlor-ui::generic.dark'),
                                'system' => trans('froxlor-ui::generic.system_default'),
                            ])
                            ->default('system')
                            ->rules(['required', 'in:light,dark,system'])
                    ]),

                Section::make('colors.base')
                    ->title(trans('froxlor-ui::generic.appearance_settings'))
                    ->description(trans('froxlor-ui::generic.appearance_settings_section_description'))
                    ->components([
                        Color::make('colors.base.color-primary')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary')),

                        Color::make('colors.base.color-primary-50')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-50')),

                        Color::make('colors.base.color-primary-100')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-100')),

                        Color::make('colors.base.color-primary-200')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-300')),

                        Color::make('colors.base.color-primary-300')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-300')),

                        Color::make('colors.base.color-primary-400')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-400')),

                        Color::make('colors.base.color-primary-500')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-500')),

                        Color::make('colors.base.color-primary-600')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-600')),

                        Color::make('colors.base.color-primary-700')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-700')),

                        Color::make('colors.base.color-primary-800')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-800')),

                        Color::make('colors.base.color-primary-900')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-900')),

                        Color::make('colors.base.color-primary-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-primary-foreground')),

                        Color::make('colors.base.color-secondary')
                            ->label(trans('froxlor-ui::generic.colors.base.color-secondary'))
                            ->col(3),

                        Color::make('colors.base.color-secondary-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-secondary-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-accent')
                            ->label(trans('froxlor-ui::generic.colors.base.color-accent'))
                            ->col(3),

                        Color::make('colors.base.color-accent-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-accent-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-muted')
                            ->label(trans('froxlor-ui::generic.colors.base.color-muted'))
                            ->col(3),

                        Color::make('colors.base.color-muted-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-muted-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-card')
                            ->label(trans('froxlor-ui::generic.colors.base.color-card'))
                            ->col(3),

                        Color::make('colors.base.color-card-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-card-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-info')
                            ->label(trans('froxlor-ui::generic.colors.base.color-info'))
                            ->col(3),

                        Color::make('colors.base.color-info-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-info-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-success')
                            ->label(trans('froxlor-ui::generic.colors.base.color-success'))
                            ->col(3),

                        Color::make('colors.base.color-success-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-success-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-warning')
                            ->label(trans('froxlor-ui::generic.colors.base.color-warning'))
                            ->col(3),

                        Color::make('colors.base.color-warning-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-warning-foreground'))
                            ->col(3),

                        Color::make('colors.base.color-danger')
                            ->label(trans('froxlor-ui::generic.colors.base.color-danger'))
                            ->col(3),

                        Color::make('colors.base.color-danger-foreground')
                            ->label(trans('froxlor-ui::generic.colors.base.color-danger-foreground'))
                            ->col(3),
                    ]),

                Section::make('colors.dark')
                    ->title(trans('froxlor-ui::generic.appearance_settings'))
                    ->description(trans('froxlor-ui::generic.appearance_settings_section_description'))
                    ->components([
                        Color::make('colors.dark.color-card')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-card'))
                            ->col(3),

                        Color::make('colors.dark.color-card-foreground')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-card-foreground'))
                            ->col(3),

                        Color::make('colors.dark.color-muted')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-muted'))
                            ->col(3),

                        Color::make('colors.dark.color-muted-foreground')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-muted-foreground'))
                            ->col(3),

                        Color::make('colors.dark.color-info')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-info'))
                            ->col(3),

                        Color::make('colors.dark.color-info-foreground')
                            ->label(trans('froxlor-ui::generic.colors.dark.color-info-foreground'))
                            ->col(3),
                    ]),
            ]);
    }
}
