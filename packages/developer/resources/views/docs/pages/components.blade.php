{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Pages Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Pages</x-ui::teaser>
            <x-ui::title>Components</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The page builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::text size="sm" class="text-zinc-300">Page components live in the <x-ui::code>Froxlor\UI\Pages\Components</x-ui::code> namespace. Combine them to compose fully featured resource screens.</x-ui::text>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Tabs &amp; Tab</x-ui::title>
        <x-ui::text>Organise sections of the page into logical groups. Tabs accept props that are passed to nested schema builders.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Pages;

                    Pages\Components\Tabs::make('tenants.show.tabs')
                        ->props(['tenant' => $tenant])
                        ->schema([
                            Pages\Components\Tab::make('tenants.show.tabs.summary')
                                ->label(trans('froxlor-core::generic.summary'))
                                ->schema([
                                    Pages\Components\Placeholder::make('hostname')
                                        ->label(trans('froxlor-core::generic.hostname')),
                                ]),

                            Pages\Components\Tab::make('tenants.show.tabs.settings')
                                ->label(trans('froxlor-core::generic.settings'))
                                ->sort(50)
                                ->schema([
                                    Pages\Components\Form::make('tenants.show.tabs.settings.form')
                                        ->schema($this->edit($tenant)->schema()),
                                ]),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Relation</x-ui::title>
        <x-ui::text>Embed a table that reuses your table columns and actions. Perfect for resource-to-resource listings.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Pages;
                    use Froxlor\UI\Tables\Actions\Action as TableAction;
                    use Froxlor\UI\Tables\Columns\TextColumn;

                    Pages\Components\Relation::make('environments')
                        ->fetch(route('api.tenants.environments.index', $tenant))
                        ->intendedRoute('environments.show', ['environment' => '{id}'])
                        ->columns([
                            TextColumn::make('name')
                                ->label(trans('froxlor-core::generic.name'))
                                ->sortable(),

                            TextColumn::make('created_at')
                                ->label(trans('froxlor-core::generic.created_at'))
                                ->sortable(),
                        ])
                        ->actions([
                            TableAction::make('create')
                                ->label(trans('froxlor-core::generic.create'))
                                ->href(route('environments.create'))
                                ->icon('plus'),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Form</x-ui::title>
        <x-ui::text>Reuse an existing form schema inline. This keeps validation, translations, and layout aligned across contexts.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Forms;
                    use Froxlor\UI\Pages;
                    use Froxlor\UI\Schemas\Components\Section;

                    Pages\Components\Form::make('tenants.show.tabs.edit.form')
                        ->schema([
                            Schemas\Components\Section::make('basic')
                                ->title(trans('froxlor-core::generic.title'))
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(trans('froxlor-core::generic.name'))
                                        ->required(),
                                ]),
                        ])
                        ->actions([
                            Forms\Actions\Action::make('back')
                                ->label(trans('froxlor-core::generic.back'))
                                ->href(route('resources.tenants.index')),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Placeholder</x-ui::title>
        <x-ui::text>Display read-only values in the page layout, such as connection details or summary metrics.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\Core\Models\Tenant;
                    use Froxlor\UI\Pages;

                    Pages\Components\Placeholder::make('hostname')
                        ->label(trans('froxlor-core::generic.hostname'))
                        ->default(fn (Tenant $tenant) => $tenant->primary_node?->hostname);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
