{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Pages Quick Start - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Pages</x-ui::teaser>
            <x-ui::title>Quick Start</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The page builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">Minimal Tenant Overview</x-ui::title>
        <x-ui::text>The snippet below renders a summary tab plus an edit action. It mirrors the structure used in <x-ui::code>TenantResource::show</x-ui::code>.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Pages;
                    use Froxlor\UI\Pages\Page;

                    return Page::make('tenants.show')
                        ->props(['tenant' => $tenant])
                        ->teaser(trans('froxlor-core::generic.tenant'))
                        ->title($tenant->name)
                        ->description('High-level tenant overview')
                        ->fetch(route('api.tenants.show', $tenant))
                        ->schema([
                            Pages\Components\Tabs::make('tenants.show.tabs')
                                ->schema([
                                    Pages\Components\Tab::make('tenants.show.tabs.summary')
                                        ->label('Summary')
                                        ->schema([
                                            Pages\Components\Placeholder::make('hostname')
                                                ->label(trans('froxlor-core::generic.hostname')),
                                        ]),
                                ]),
                        ])
                        ->actions([
                            Pages\Actions\Action::make('edit')
                                ->label(trans('froxlor-core::generic.edit'))
                                ->intendedRoute('tenants.edit', ['tenant' => '{id}'])
                                ->icon('pen'),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Relational Data &amp; Inline Forms</x-ui::title>
        <x-ui::text>This pattern keeps related resources and inline edit capabilities together. The relation component reuses table columns for consistency, while the embedded form mirrors the create/edit schema.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Forms;
                    use Froxlor\UI\Schemas\Components\Section;
                    use Froxlor\UI\Tables\Columns\TextColumn;

                    Pages\Components\Tabs::make('tenants.show.tabs')
                        ->props(['tenant' => $tenant])
                        ->schema([
                            Pages\Components\Tab::make('tenants.show.tabs.environments')
                                ->label('Environments')
                                ->schema([
                                    Pages\Components\Relation::make('environments')
                                        ->fetch(route('api.tenants.environments.index', $tenant))
                                        ->intendedRoute('tenants.edit', ['tenant' => '{id}'])
                                        ->columns([
                                            TextColumn::make('name')
                                                ->label(trans('froxlor-core::generic.name')),
                                        ]),
                                ]),

                            Pages\Components\Tab::make('tenants.show.tabs.edit')
                                ->label(trans('froxlor-core::generic.edit'))
                                ->schema([
                                    Pages\Components\Form::make('tenants.show.tabs.edit.form')
                                        ->schema([
                                            Schemas\Components\Section::make('section_a')
                                                ->title(trans('froxlor-core::generic.title'))
                                                ->schema([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label(trans('froxlor-core::generic.name'))
                                                        ->required(),
                                                ]),
                                        ]),
                                ]),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
