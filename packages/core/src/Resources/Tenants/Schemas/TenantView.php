<?php

namespace Froxlor\Core\Resources\Tenants\Schemas;

use Froxlor\Core\Models\Tenant;
use Froxlor\UI\Schemas;

class TenantView
{
    public static function schema(Tenant $tenant): array
    {
        return [
            Schemas\Components\Section::make('tenant.details')
                ->title(trans('froxlor-core::generic.details'))
                ->components([
                    Schemas\Components\Text::make('name')
                        ->label(trans('froxlor-core::generic.name')),

                    Schemas\Components\Text::make('description')
                        ->label(trans('froxlor-core::generic.description')),

                    Schemas\Components\Text::make('plan.name')
                        ->label(trans('froxlor-core::generic.plan')),

                    Schemas\Components\Text::make('created_at')
                        ->label(trans('froxlor-core::generic.created_at')),
                ]),
        ];
    }
}
