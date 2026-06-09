<?php

namespace Froxlor\Core\Resources\Users\Schemas;

use Froxlor\Core\Support\Api;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class EditUserForm
{
    public static function schema(?string $tenantId = null, bool $includeTenantSelect = true): array
    {
        $tenantField = $includeTenantSelect
            ? [
                Forms\Components\Select::make('tenant_id')
                    ->label(trans('froxlor-core::generic.tenant'))
                    ->options(self::tenantOptions()),
            ]
            : [];

        return [
            Schemas\Components\Group::make('group_a')
                ->components([
                    Schemas\Components\Section::make('section_a')
                        ->title(trans('froxlor-core::generic.title'))
                        ->description('The account\'s profile information and email address.')
                        ->components([
                            Forms\Components\TextInput::make('first_name')
                                ->label(trans('froxlor-core::generic.first_name'))
                                ->required()
                                ->col(3),

                            Forms\Components\TextInput::make('last_name')
                                ->label(trans('froxlor-core::generic.last_name'))
                                ->required()
                                ->col(3),

                            Forms\Components\TextInput::make('company_name')
                                ->label(trans('froxlor-core::generic.company_name')),
                        ]),

                    Schemas\Components\Section::make('section_b')
                        ->title(trans('froxlor-core::generic.title'))
                        ->description('Account settings and set e-mail preferences.')
                        ->components([
                            Forms\Components\TextInput::make('email')
                                ->label(trans('froxlor-core::generic.email'))
                                ->required()
                                ->email(),

                            Forms\Components\TextInput::make('password')
                                ->label(trans('froxlor-core::generic.password'))
                                ->password(),
                        ]),

                    Schemas\Components\Section::make('section_c')
                        ->title(trans('froxlor-core::generic.title'))
                        ->description('Assign tenant access and role.')
                        ->components([
                            ...$tenantField,
                            Forms\Components\Select::make('role_id')
                                ->label(trans('froxlor-core::generic.role'))
                                ->options(self::roleOptions($tenantId)),

                            Forms\Components\Select::make('plan_id')
                                ->label(trans('froxlor-core::generic.plan'))
                                ->options(self::planOptions($tenantId)),
                        ]),
                ])
                ->colSpan(2),

            Schemas\Components\Group::make('group_b')
                ->components([
                    Schemas\Components\Section::make('section_c')
                        ->title(trans('froxlor-core::generic.title'))
                        ->description('Lorem ipsum dolor sit amet, consetetur sadipscing elitr.')
                        ->components([
                            Schemas\Components\Text::make('created_at')
                                ->label(trans('froxlor-core::generic.created_at'))
                                ->required(),
                        ]),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('auth.users.index')),
        ];
    }

    private static function tenantOptions(): array
    {
        return self::fetchOptions(route('api.tenants.index'));
    }

    private static function roleOptions(?string $tenantId = null): array
    {
        if ($tenantId) {
            return self::fetchOptions(route('api.tenants.roles.index', ['tenant' => $tenantId]));
        }

        return self::fetchOptions(route('api.roles.index'));
    }

    private static function planOptions(?string $tenantId = null): array
    {
        if ($tenantId) {
            return self::fetchOptions(route('api.tenants.plans.index', ['tenant' => $tenantId]));
        }

        return self::fetchOptions(route('api.plans.index'));
    }

    private static function fetchOptions(string $uri): array
    {
        try {
            $response = Api::request('GET', $uri);
            $items = $response->data();
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($items as $item) {
            if (!isset($item['id'])) {
                continue;
            }
            $options[$item['id']] = $item['name'] ?? $item['id'];
        }

        return $options;
    }
}
