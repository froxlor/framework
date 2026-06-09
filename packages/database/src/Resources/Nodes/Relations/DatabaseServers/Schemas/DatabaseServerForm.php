<?php

namespace Froxlor\Database\Resources\Nodes\Relations\DatabaseServers\Schemas;

use Froxlor\UI\Forms;
use Froxlor\UI\Schemas\Components\Section;

class DatabaseServerForm
{
    public static function schema(): array
    {
        return [
            Section::make('database_server_details')
                ->title('Database server')
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),
                    Forms\Components\Select::make('driver')
                        ->label(trans('froxlor-core::generic.driver'))
                        ->options([
                            'mysql' => 'MySQL',
                            'mariadb' => 'MariaDB',
                            'pgsql' => 'PostgreSQL',
                        ]),
                    Forms\Components\TextInput::make('host')
                        ->label(trans('froxlor-core::generic.hostname'))
                        ->required(),
                    Forms\Components\TextInput::make('port')
                        ->label(trans('froxlor-core::generic.port'))
                        ->integer()
                        ->required(),
                ]),
            Section::make('database_server_access')
                ->title(trans('froxlor-core::generic.authentication'))
                ->components([
                    Forms\Components\TextInput::make('admin_username')
                        ->label('Admin username'),
                    Forms\Components\TextInput::make('admin_password')
                        ->label('Admin password')
                        ->password(),
                    Forms\Components\Select::make('supports_per_environment_users')
                        ->label('Environment users')
                        ->options([
                            1 => trans('froxlor-core::generic.yes'),
                            0 => trans('froxlor-core::generic.no'),
                        ]),
                    Forms\Components\TextInput::make('max_databases')
                        ->label('Max databases')
                        ->integer(),
                ]),
        ];
    }
}
