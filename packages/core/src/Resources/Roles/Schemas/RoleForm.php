<?php

namespace Froxlor\Core\Resources\Roles\Schemas;

use Froxlor\Core\Models\Role;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class RoleForm
{
    public static function schema(bool $includeMeta = true): array
    {
        $metaGroup = $includeMeta
            ? [
                Schemas\Components\Group::make('roles.form.group_b')
                    ->components([
                        Schemas\Components\Section::make('roles.form.meta')
                            ->title(trans('froxlor-core::generic.title'))
                            ->description(trans('froxlor-core::generic.overview'))
                            ->components([
                                Schemas\Components\Text::make('members_count')
                                    ->label(trans('froxlor-core::generic.members_count')),

                                Schemas\Components\Text::make('created_at')
                                    ->label(trans('froxlor-core::generic.created_at')),

                                Schemas\Components\Text::make('updated_at')
                                    ->label(trans('froxlor-core::generic.updated_at')),
                            ]),
                    ]),
            ]
            : [];

        return [
            Schemas\Components\Group::make('roles.form.group_a')
                ->components([
                    Schemas\Components\Section::make('roles.form.main')
                        ->title(trans('froxlor-core::generic.title'))
                        ->description(trans('froxlor-core::generic.role'))
                        ->components([
                            Forms\Components\TextInput::make('name')
                                ->label(trans('froxlor-core::generic.name'))
                                ->default('Role ' . str()->random(4))
                                ->required(),

                            Forms\Components\TextInput::make('description')
                                ->label(trans('froxlor-core::generic.description')),
                        ]),
                ])
                ->colSpan(2),

            ...$metaGroup,
        ];
    }

    public static function createActions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('auth.roles.index')),
        ];
    }

    public static function editActions(Role $role): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('auth.roles.show', $role)),
        ];
    }
}
