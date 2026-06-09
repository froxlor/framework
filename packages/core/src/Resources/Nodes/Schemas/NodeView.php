<?php

namespace Froxlor\Core\Resources\Nodes\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\UI\Schemas;
use Froxlor\UI\Widgets\Components\ChartWidget;
use Froxlor\UI\Widgets\Components\InfoWidget;

class NodeView
{
    public static function schema(Node $node): array
    {
        return [
            Schemas\Components\Tabs::make('resources.nodes.show.tabs')
                ->props(['node' => $node])
                ->components([
                    Schemas\Components\Tab::make('resources.nodes.show.tabs.details')
                        ->sort(1)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            Schemas\Schema::make('resources.nodes.show.details')
                                ->components([
                                    Schemas\Components\Group::make('resources.nodes.show.details.primary')
                                        ->components([
                                            Schemas\Schema::make('resources.nodes.show.details.widgets')
                                                ->components(self::widgets($node))
                                                ->cols(3),

                                            Schemas\Schema::make('resources.nodes.show.details.usage')
                                                ->components(self::usageCharts($node))
                                                ->cols(3),
                                        ])
                                        ->colSpan(3),

                                    Schemas\Components\Group::make('resources.nodes.show.details.primary')
                                        ->components([
                                            Schemas\Components\Section::make('resources.nodes.show.details.system')
                                                ->title(trans('froxlor-core::generic.system'))
                                                ->description(trans('froxlor-core::generic.node_system_description'))
                                                ->components([
                                                    Schemas\Components\Text::make('name')
                                                        ->label(trans('froxlor-core::generic.name')),

                                                    Schemas\Components\Text::make('description')
                                                        ->label(trans('froxlor-core::generic.description')),

                                                    Schemas\Components\Text::make('adapter')
                                                        ->label(trans('froxlor-core::generic.adapter')),

                                                    Schemas\Components\Text::make('os.pretty_name')
                                                        ->label('Platform')
                                                        ->default($node->platform()->label()),

                                                    Schemas\Components\Text::make('os.key')
                                                        ->label('Platform key')
                                                        ->default($node->platform()->key()),

                                                    Schemas\Components\Text::make('os.supported')
                                                        ->label('Platform support')
                                                        ->default($node->platform()->supported ? trans('froxlor-core::generic.yes') : trans('froxlor-core::generic.no')),

                                                    Schemas\Components\Text::make('hostname')
                                                        ->label(trans('froxlor-core::generic.hostname')),

                                                    Schemas\Components\Text::make('username')
                                                        ->label(trans('froxlor-core::generic.username')),

                                                    Schemas\Components\Text::make('sudo')
                                                        ->label(trans('froxlor-core::generic.sudo'))
                                                        ->default($node->sudo ? trans('froxlor-core::generic.yes') : trans('froxlor-core::generic.no')),
                                                ]),

                                            Schemas\Components\Section::make('resources.nodes.show.details.network')
                                                ->title(trans('froxlor-core::generic.network'))
                                                ->description(trans('froxlor-core::generic.node_network_description'))
                                                ->components([
                                                    Schemas\Components\Text::make('interface_bind_addresses')
                                                        ->label(trans('froxlor-core::generic.bind_addresses'))
                                                        ->default($node->nodeInterfaces->pluck('bind_addr')->filter()->join(', ') ?: trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('interface_nat_addresses')
                                                        ->label(trans('froxlor-core::generic.nat_addresses'))
                                                        ->default($node->nodeInterfaces->pluck('nat_addr')->filter()->join(', ') ?: trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('environment_names')
                                                        ->label(trans('froxlor-core::generic.environments'))
                                                        ->default($node->environments->pluck('name')->join(', ') ?: trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('tenant_names')
                                                        ->label(trans('froxlor-core::generic.tenants'))
                                                        ->default($node->environments->pluck('tenant.name')->filter()->unique()->join(', ') ?: trans('froxlor-core::generic.none')),
                                                ]),
                                        ])
                                        ->colSpan(2),

                                    Schemas\Components\Group::make('resources.nodes.show.details.meta')
                                        ->components([
                                            Schemas\Components\Section::make('resources.nodes.show.details.meta.section')
                                                ->title(trans('froxlor-core::generic.title'))
                                                ->description(trans('froxlor-core::generic.node_meta_description'))
                                                ->components([
                                                    Schemas\Components\Text::make('id')
                                                        ->label('ID'),

                                                    Schemas\Components\Text::make('created_at')
                                                        ->label(trans('froxlor-core::generic.created_at')),

                                                    Schemas\Components\Text::make('updated_at')
                                                        ->label(trans('froxlor-core::generic.updated_at')),
                                                ]),
                                        ]),
                                ])
                                ->cols(3),
                        ]),

                    Schemas\Components\Tab::make('resources.nodes.show.tabs.edit')
                        ->sort(2)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('resources.nodes.show.edit')
                                ->components(NodeForm::schema())
                                ->cols(3),
                        ]),
                ]),
        ];
    }

    public static function actions(Node $node): array
    {
        return [
            Schemas\Actions\Action::make('edit')
                ->label(trans('froxlor-core::generic.edit'))
                ->href(route('resources.nodes.edit', ['node' => $node])),

            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => trans('froxlor-core::generic.nodes')]))
                ->href(route('resources.nodes.index')),
        ];
    }

    private static function utilization(Node $node, string $key): int
    {
        return (int) round((float) data_get($node->properties, "{$key}.utilized", 0));
    }

    private static function widgets(Node $node): array
    {
        return [
            InfoWidget::make('environments_count')
                ->title(trans('froxlor-core::generic.environments'))
                ->description(trans('froxlor-core::generic.node_environments_description'))
                ->value($node->environments->count())
                ->trend($node->environments->pluck('tenant_id')->filter()->unique()->count() . ' ' . trans('froxlor-core::generic.tenants'), 'secondary')
                ->icon('boxes')
                ->tone('primary'),

            InfoWidget::make('node_interfaces_count')
                ->title(trans('froxlor-core::generic.network_interfaces'))
                ->description(trans('froxlor-core::generic.node_interfaces_description'))
                ->value($node->nodeInterfaces->count())
                ->trend($node->nodeInterfaces->pluck('bind_addr')->filter()->count() . ' ' . trans('froxlor-core::generic.bound_addresses'), 'success')
                ->icon('network')
                ->tone('success'),

            InfoWidget::make('latest_unix_name')
                ->title(trans('froxlor-core::generic.next_unix_name'))
                ->description(trans('froxlor-core::generic.node_next_identity_description'))
                ->value($node->latestUnixName)
                ->trend($node->latestGuid . ' ' . trans('froxlor-core::generic.next_guid'), 'secondary')
                ->icon('badge-plus')
                ->tone('secondary'),
        ];
    }

    private static function utilizationSeries(Node $node, string $key): array
    {
        $used = self::utilization($node, $key);

        return [
            ['label' => trans('froxlor-core::generic.used'), 'value' => $used],
            ['label' => trans('froxlor-core::generic.free'), 'value' => max(0, 100 - $used)],
        ];
    }

    private static function usageCharts(Node $node): array
    {
        return [
            ChartWidget::make('cpu_usage')
                ->title(trans('froxlor-core::generic.cpu_usage'))
                ->description(trans('froxlor-core::generic.node_resource_usage_description'))
                ->value(self::utilization($node, 'cpu'))
                ->suffix('%')
                ->icon('cpu')
                ->tone('primary')
                ->chart('doughnut')
                ->series(self::utilizationSeries($node, 'cpu'))
                ->height(260)
                ->showSummary(false),

            ChartWidget::make('memory_usage')
                ->title(trans('froxlor-core::generic.memory_usage'))
                ->description(trans('froxlor-core::generic.node_resource_usage_description'))
                ->value(self::utilization($node, 'memory'))
                ->suffix('%')
                ->icon('memory-stick')
                ->tone('warning')
                ->chart('doughnut')
                ->series(self::utilizationSeries($node, 'memory'))
                ->height(260)
                ->showSummary(false),

            ChartWidget::make('disk_usage')
                ->title(trans('froxlor-core::generic.disk_usage'))
                ->description(trans('froxlor-core::generic.node_resource_usage_description'))
                ->value(self::utilization($node, 'disk'))
                ->suffix('%')
                ->icon('hard-drive')
                ->tone('danger')
                ->chart('doughnut')
                ->series(self::utilizationSeries($node, 'disk'))
                ->height(260)
                ->showSummary(false),
        ];
    }
}
