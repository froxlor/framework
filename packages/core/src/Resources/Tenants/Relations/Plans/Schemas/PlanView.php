<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Plans\Schemas;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\UI\Schemas;
use Froxlor\UI\Tables;

class PlanView
{
    public static function schema(Tenant $tenant, Plan $plan): array
    {
        return [
            Schemas\Components\Tabs::make('plans')
                ->props(['tenant' => $tenant, 'plan' => $plan])
                ->components([
                    //
                ]),
        ];
    }

    public static function actions(Tenant $tenant, Plan $plan): array
    {
        return [
            Tables\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $tenant->name]))
                ->href(route('tenants.show', ['tenant' => $tenant]))
                ->icon('circle-chevron-left'),
        ];
    }
}
