<?php

namespace Froxlor\Core\Resources\Overview\Schemas;

use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\UI\Schemas;
use Froxlor\UI\Widgets\Components\ChartWidget;
use Froxlor\UI\Widgets\Components\InfoWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OverviewSchema
{
    public static function schema(): array
    {
        return [
            Schemas\Schema::make('overview.stats')
                ->gridCols('grid-cols-1 md:grid-cols-2 xl:grid-cols-3')
                ->components([
                    InfoWidget::make('users_count')
                        ->title(trans('froxlor-core::generic.users'))
                        ->description(trans('froxlor-core::generic.overview_users_description'))
                        ->value(fn() => User::query()->count())
                        ->trend(fn() => self::createdLastDays(User::class, 7) . ' ' . trans('froxlor-core::generic.new_last_seven_days'), 'success')
                        ->icon('users')
                        ->href(route('auth.users.index'))
                        ->tone('primary'),

                    InfoWidget::make('roles_count')
                        ->title(trans('froxlor-core::generic.roles'))
                        ->description(trans('froxlor-core::generic.overview_roles_description'))
                        ->value(fn() => Role::query()->count())
                        ->trend(fn() => Role::query()->whereNull('tenant_id')->count() . ' ' . trans('froxlor-core::generic.global'), 'secondary')
                        ->icon('folder-key')
                        ->href(route('auth.roles.index'))
                        ->tone('secondary'),

                    InfoWidget::make('tenants_count')
                        ->title(trans('froxlor-core::generic.tenants'))
                        ->description(trans('froxlor-core::generic.overview_tenants_description'))
                        ->value(fn() => Tenant::query()->count())
                        ->trend(fn() => self::managedSubTenantCount() . ' ' . trans('froxlor-core::generic.subtenants'), 'warning')
                        ->icon('square-library')
                        ->href(route('resources.tenants.index'))
                        ->tone('warning'),

                    InfoWidget::make('plans_count')
                        ->title(trans('froxlor-core::generic.plans'))
                        ->description(trans('froxlor-core::generic.overview_plans_description'))
                        ->value(fn() => Plan::query()->count())
                        ->trend(fn() => Plan::query()->whereNull('tenant_id')->count() . ' ' . trans('froxlor-core::generic.global'), 'success')
                        ->icon('receipt-text')
                        ->href(route('resources.plans.index'))
                        ->tone('success'),

                    InfoWidget::make('environments_count')
                        ->title(trans('froxlor-core::generic.environments'))
                        ->description(trans('froxlor-core::generic.overview_environments_description'))
                        ->value(fn() => Environment::query()->count())
                        ->trend(fn() => self::createdLastDays(Environment::class, 7) . ' ' . trans('froxlor-core::generic.new_last_seven_days'), 'primary')
                        ->icon('boxes')
                        ->tone('primary'),

                    InfoWidget::make('nodes_count')
                        ->title(trans('froxlor-core::generic.nodes'))
                        ->description(trans('froxlor-core::generic.overview_nodes_description'))
                        ->value(fn() => Node::query()->count())
                        ->trend(fn() => self::nodesWithEnvironmentsCount() . ' ' . trans('froxlor-core::generic.in_use'), 'danger')
                        ->icon('hard-drive')
                        ->href(route('resources.nodes.index'))
                        ->tone('danger'),
                ])
                ->cols(3),

            ChartWidget::make('resource_distribution')
                ->title(trans('froxlor-core::generic.resource_distribution'))
                ->description(trans('froxlor-core::generic.resource_distribution_description'))
                ->value(fn() => self::totalManagedResources())
                ->icon('chart-pie')
                ->tone('primary')
                ->chart('doughnut')
                ->series(fn() => self::resourceDistributionSeries())
                ->height(320)
                ->showSummary(false)
                ->footer(trans('froxlor-core::generic.resource_distribution_footer'))
                ->href(route('resources.tenants.index'))
                ->colSpan(2),

            Schemas\Components\Group::make('overview.side-panel')
                ->components([
                    Schemas\Components\Section::make('overview.recent')
                        ->fullHeight()
                        ->title(trans('froxlor-core::generic.recent_activity'))
                        ->description(trans('froxlor-core::generic.overview_activity_description'))
                        ->components([
                            Schemas\Components\Text::make('latest_audit_event')
                                ->label(trans('froxlor-core::generic.latest_audit_event'))
                                ->default(fn() => self::latestAuditEvent()),

                            Schemas\Components\Text::make('latest_user')
                                ->label(trans('froxlor-core::generic.latest_user'))
                                ->default(fn() => self::latestUser()),

                            Schemas\Components\Text::make('latest_tenant')
                                ->label(trans('froxlor-core::generic.latest_tenant'))
                                ->default(fn() => self::latestTenant()),

                            Schemas\Components\Text::make('latest_environment')
                                ->label(trans('froxlor-core::generic.latest_environment'))
                                ->default(fn() => self::latestEnvironment()),

                            Schemas\Components\Text::make('latest_node')
                                ->label(trans('froxlor-core::generic.latest_node'))
                                ->default(fn() => self::latestNode()),
                        ]),
                ]),

            ChartWidget::make('recent_signups')
                ->title(trans('froxlor-core::generic.user_growth'))
                ->description(trans('froxlor-core::generic.user_growth_description'))
                ->value(fn() => self::createdLastDays(User::class, 7))
                ->suffix(' new')
                ->icon('trending-up')
                ->tone('success')
                ->chart('line')
                ->series(fn() => self::dailySeries(User::class, 7))
                ->height(280)
                ->footer(trans('froxlor-core::generic.last_seven_days'))
                ->href(route('auth.users.index'))
                ->colSpan(2),

            ChartWidget::make('recent_audit_activity')
                ->title(trans('froxlor-core::generic.audit_activity'))
                ->description(trans('froxlor-core::generic.audit_activity_description'))
                ->value(fn() => self::createdLastDays(AuditLog::class, 7))
                ->suffix(' events')
                ->icon('file-clock')
                ->tone('warning')
                ->chart('bar')
                ->series(fn() => self::dailySeries(AuditLog::class, 7))
                ->height(280)
                ->footer(trans('froxlor-core::generic.last_seven_days'))
                ->href(route('audit-log.index'))
                ->colSpan(1),

            Schemas\Components\Group::make('overview.configuration')
                ->components([
                    Schemas\Components\Section::make('overview.highlights')
                        ->fullHeight()
                        ->title(trans('froxlor-core::generic.configuration'))
                        ->description(trans('froxlor-core::generic.overview_highlights_description'))
                        ->components([
                            Schemas\Components\Text::make('global_roles_count')
                                ->label(trans('froxlor-core::generic.global_roles'))
                                ->default(fn() => Role::query()->whereNull('tenant_id')->count()),

                            Schemas\Components\Text::make('tenant_roles_count')
                                ->label(trans('froxlor-core::generic.tenant_roles'))
                                ->default(fn() => Role::query()->whereNotNull('tenant_id')->count()),

                            Schemas\Components\Text::make('global_plans_count')
                                ->label(trans('froxlor-core::generic.global_plans'))
                                ->default(fn() => Plan::query()->whereNull('tenant_id')->count()),

                            Schemas\Components\Text::make('tenant_plans_count')
                                ->label(trans('froxlor-core::generic.tenant_plans'))
                                ->default(fn() => Plan::query()->whereNotNull('tenant_id')->count()),

                            Schemas\Components\Text::make('latest_update_at')
                                ->label(trans('froxlor-core::generic.updated_at'))
                                ->default(fn() => self::latestUpdateAt()),
                        ]),
                ]),
        ];
    }

    private static function totalManagedResources(): int
    {
        return User::query()->count()
            + Role::query()->count()
            + Tenant::query()->count()
            + Plan::query()->count()
            + Environment::query()->count()
            + Node::query()->count();
    }

    private static function managedSubTenantCount(): int
    {
        return Tenant::query()->whereNotNull('parent_tenant_id')->count();
    }

    private static function nodesWithEnvironmentsCount(): int
    {
        return Node::query()->get()->filter(fn (Node $node) => $node->environments_count > 0)->count();
    }

    private static function resourceDistributionSeries(): array
    {
        return [
            ['label' => trans('froxlor-core::generic.users'), 'value' => User::query()->count()],
            ['label' => trans('froxlor-core::generic.roles'), 'value' => Role::query()->count()],
            ['label' => trans('froxlor-core::generic.tenants'), 'value' => Tenant::query()->count()],
            ['label' => trans('froxlor-core::generic.plans'), 'value' => Plan::query()->count()],
            ['label' => trans('froxlor-core::generic.environments'), 'value' => Environment::query()->count()],
            ['label' => trans('froxlor-core::generic.nodes'), 'value' => Node::query()->count()],
        ];
    }

    private static function dailySeries(string $modelClass, int $days = 7): array
    {
        /** @var Model $modelClass */
        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $counts = $modelClass::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at'])
            ->groupBy(fn($record) => $record->created_at->format('Y-m-d'))
            ->map(fn($items) => $items->count());

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start, $counts) {
                $day = $start->copy()->addDays($offset);
                $key = $day->format('Y-m-d');

                return [
                    'label' => $day->format('D j'),
                    'value' => (int) ($counts[$key] ?? 0),
                ];
            })
            ->all();
    }

    private static function createdLastDays(string $modelClass, int $days = 7): int
    {
        /** @var Model $modelClass */
        return $modelClass::query()
            ->whereBetween('created_at', [
                Carbon::now()->subDays($days - 1)->startOfDay(),
                Carbon::now()->endOfDay(),
            ])
            ->count();
    }

    private static function latestAuditEvent(): string
    {
        $log = AuditLog::query()->latest()->first();

        if (!$log) {
            return trans('froxlor-core::generic.none');
        }

        return trim(sprintf(
            '%s%s%s',
            $log->action,
            $log->tenant_id ? ' · tenant ' . $log->tenant_id : '',
            self::formatTimestampSuffix($log->created_at?->toDateTimeString())
        ));
    }

    private static function latestUser(): string
    {
        $user = User::query()->latest()->first();

        if (!$user) {
            return trans('froxlor-core::generic.none');
        }

        return $user->name . self::formatTimestampSuffix($user->created_at?->toDateTimeString());
    }

    private static function latestTenant(): string
    {
        $tenant = Tenant::query()->latest()->first();

        if (!$tenant) {
            return trans('froxlor-core::generic.none');
        }

        return $tenant->name . self::formatTimestampSuffix($tenant->created_at?->toDateTimeString());
    }

    private static function latestEnvironment(): string
    {
        $environment = Environment::query()->latest()->first();

        if (!$environment) {
            return trans('froxlor-core::generic.none');
        }

        return $environment->name . self::formatTimestampSuffix($environment->created_at?->toDateTimeString());
    }

    private static function latestNode(): string
    {
        $node = Node::query()->latest()->first();

        if (!$node) {
            return trans('froxlor-core::generic.none');
        }

        return $node->name . self::formatTimestampSuffix($node->created_at?->toDateTimeString());
    }

    private static function latestUpdateAt(): string
    {
        $timestamps = array_filter([
            User::query()->max('updated_at'),
            Role::query()->max('updated_at'),
            Tenant::query()->max('updated_at'),
            Plan::query()->max('updated_at'),
            Environment::query()->max('updated_at'),
            Node::query()->max('updated_at'),
            AuditLog::query()->max('updated_at'),
        ]);

        if (!$timestamps) {
            return trans('froxlor-core::generic.none');
        }

        rsort($timestamps);

        return (string) $timestamps[0];
    }

    private static function formatTimestampSuffix(?string $timestamp): string
    {
        return $timestamp ? ' · ' . $timestamp : '';
    }
}
