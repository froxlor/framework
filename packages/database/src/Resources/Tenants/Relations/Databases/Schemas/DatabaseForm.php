<?php

namespace Froxlor\Database\Resources\Tenants\Relations\Databases\Schemas;

use Froxlor\Core\Models\Environment;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas\Components\Section;

class DatabaseForm
{
    public static function schema(Environment $environment): array
    {
        return [
            Section::make('database_details')
                ->title('Database')
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),
                    Forms\Components\TextInput::make('database_name')
                        ->label('Database name'),
                    Forms\Components\TextInput::make('username')
                        ->label(trans('froxlor-core::generic.username')),
                    Forms\Components\TextInput::make('password')
                        ->label(trans('froxlor-core::generic.password'))
                        ->password(),
                ]),
            Section::make('database_configuration')
                ->title(trans('froxlor-core::generic.configuration'))
                ->components([
                    Forms\Components\Select::make('engine')
                        ->label('Engine')
                        ->options([
                            'mysql' => 'MySQL',
                            'mariadb' => 'MariaDB',
                        ]),
                    Forms\Components\TextInput::make('node_database_service')
                        ->label('Node database service')
                        ->default(function () use ($environment) {
                            $node = $environment->nodes()->wherePivot('mode', 'main')->first() ?? $environment->nodes()->first();

                            if (! $node) {
                                return 'No node assigned';
                            }

                            if (! $node->databaseServer) {
                                return $node->name . ' - no database service configured';
                            }

                            return $node->name . ' - ' . $node->databaseServer->name;
                        })
                        ->rules(['nullable', 'string']),
                    Forms\Components\TextInput::make('charset')
                        ->label('Charset'),
                    Forms\Components\TextInput::make('collation')
                        ->label('Collation'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'active' => 'Active',
                            'error' => 'Error',
                        ]),
                ]),
        ];
    }
}
