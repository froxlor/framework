<?php

namespace Froxlor\Database\Resources\Tenants\Relations\Databases\Schemas;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Database\Models\Database;
use Froxlor\UI\Schemas;
use Froxlor\UI\Tables;

class DatabaseView
{
    public static function schema(Tenant $tenant, Environment $environment, Database $database): array
    {
        return [
            Schemas\Components\Tabs::make('tenants.environments.databases.show.tabs')
                ->components([
                    Schemas\Components\Tab::make('details')
                        ->sort(1)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            Schemas\Components\Section::make('database_meta')
                                ->title('Database')
                                ->components([
                                    Schemas\Components\Text::make('name')
                                        ->label(trans('froxlor-core::generic.name'))
                                        ->default(fn() => $database->name),
                                    Schemas\Components\Text::make('database_name')
                                        ->label('Database name')
                                        ->default(fn() => $database->database_name ?: $database->name),
                                    Schemas\Components\Text::make('username')
                                        ->label(trans('froxlor-core::generic.username'))
                                        ->default(fn() => $database->username ?: '-'),
                                    Schemas\Components\Text::make('status')
                                        ->label('Status')
                                        ->default(fn() => $database->status_label),
                                    Schemas\Components\Text::make('engine')
                                        ->label('Engine')
                                        ->default(fn() => $database->engine),
                                ]),
                        ]),
                ]),
        ];
    }

    public static function actions(Tenant $tenant, Environment $environment, Database $database): array
    {
        return [
            Tables\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $environment->name]))
                ->href(route('tenants.environments.show', ['tenant' => $tenant, 'environment' => $environment]))
                ->icon('circle-chevron-left'),
            Tables\Actions\Action::make('edit')
                ->label(trans('froxlor-core::generic.edit'))
                ->href(route('tenants.environments.databases.edit', [
                    'tenant' => $tenant,
                    'environment' => $environment,
                    'database' => $database,
                ]))
                ->icon('pencil'),
        ];
    }
}
