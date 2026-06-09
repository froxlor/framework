<?php

namespace Froxlor\Ftp\Resources\Nodes\Relations\FtpServices\Schemas;

use Froxlor\UI\Forms;
use Froxlor\UI\Schemas\Components\Section;

class FtpServiceForm
{
    public static function schema(): array
    {
        return [
            Section::make('ftp_service_details')
                ->title('FTP service')
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),
                    Forms\Components\Select::make('driver')
                        ->label(trans('froxlor-core::generic.driver'))
                        ->options([
                            'vsftpd' => 'vsftpd',
                        ]),
                    Forms\Components\TextInput::make('listen_address')
                        ->label('Listen address')
                        ->required(),
                    Forms\Components\TextInput::make('port')
                        ->label(trans('froxlor-core::generic.port'))
                        ->integer()
                        ->required(),
                ]),
            Section::make('ftp_service_access')
                ->title('Access')
                ->components([
                    Forms\Components\Select::make('allow_local_users')
                        ->label('Allow local users')
                        ->options([
                            1 => trans('froxlor-core::generic.yes'),
                            0 => trans('froxlor-core::generic.no'),
                        ]),
                    Forms\Components\Select::make('allow_write')
                        ->label('Allow write')
                        ->options([
                            1 => trans('froxlor-core::generic.yes'),
                            0 => trans('froxlor-core::generic.no'),
                        ]),
                    Forms\Components\Select::make('chroot_local_users')
                        ->label('Chroot local users')
                        ->options([
                            1 => trans('froxlor-core::generic.yes'),
                            0 => trans('froxlor-core::generic.no'),
                        ]),
                    Forms\Components\Select::make('allow_writable_chroot')
                        ->label('Allow writable chroot')
                        ->options([
                            1 => trans('froxlor-core::generic.yes'),
                            0 => trans('froxlor-core::generic.no'),
                        ]),
                    Forms\Components\TextInput::make('passive_min_port')
                        ->label('Passive min port')
                        ->integer()
                        ->required(),
                    Forms\Components\TextInput::make('passive_max_port')
                        ->label('Passive max port')
                        ->integer()
                        ->required(),
                ]),
        ];
    }
}
