{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Tables Quick Start - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Tables</x-ui::teaser>
            <x-ui::title>Quick Start</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The table builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">Columns</x-ui::title>
        <x-ui::text><x-ui::code>columns(...)</x-ui::code></x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;
                    use Froxlor\UI\Tables;

                    public function index(): Table
                    {
                        return Table::make()
                            ->columns([
                                Tables\Columns\TextColumn::make('name')
                                    ->label('froxlor-core::generic.name')
                                    ->sortable(),

                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Column Actions</x-ui::title>
        <x-ui::text><x-ui::code>columnActions(...)</x-ui::code> showcases column actions that link to the resource row.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;
                    use Froxlor\UI\Tables;

                    public function index(): Table
                    {
                        return Table::make()
                            ->columns([
                                // ...
                            ])
                            ->columnActions([
                                Tables\ColumnActions\Action::make('show')
                                    ->label('froxlor-core::generic.show')
                                    ->intendedRoute('resources.nodes.edit', ['node' => '{id}'])
                                    ->icon('eye'),

                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Bulk Actions</x-ui::title>
        <x-ui::text><x-ui::code>bulkActions(...)</x-ui::code> enables multi-row selection. Actions can either submit the selected row keys as <x-ui::code>selected[]</x-ui::code> or execute a server-side <x-ui::code>handler(...)</x-ui::code>.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;
                    use Froxlor\UI\Tables;

                    public function index(): Table
                    {
                        return Table::make()
                            ->fetch(route('api.nodes.index'))
                            ->columns([
                                // ...
                            ])
                            ->selectionKey('id')
                            ->bulkActions([
                                Tables\Actions\Action::make('delete')
                                    ->label(trans('froxlor-core::generic.delete'))
                                    ->icon('trash')
                                    ->handler(function (array $selected) {
                                        foreach ($selected as $nodeId) {
                                            // delete each row individually here
                                        }
                                    }),
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Actions</x-ui::title>
        <x-ui::text><x-ui::code>actions(...)</x-ui::code> showcases actions that link to other pages.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;
                    use Froxlor\UI\Tables;

                    public function index(): Table
                    {
                        return Table::make()
                        ->columns([
                            // ...
                        ])
                        ->actions([
                            Tables\Actions\Action::make('create')
                                ->label('froxlor-core::generic.create')
                                ->href(route('resources.nodes.create'))
                                ->visible(fn() => true)
                                ->icon('plus'),

                            // ...
                        ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Title</x-ui::title>
        <x-ui::text><x-ui::code>title(...)</x-ui::code></x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->title('froxlor::generic.text')
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Description</x-ui::title>
        <x-ui::text><x-ui::code>description(...)</x-ui::code></x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->description('froxlor::generic.text')
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Fetch</x-ui::title>
        <x-ui::text><x-ui::code>fetch(...)</x-ui::code></x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->fetch(route('api.nodes.index'))
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Filters</x-ui::title>
        <x-ui::text><x-ui::code>filters(...)</x-ui::code></x-ui::text>

        <x-ui::alert variant="warning">
            <x-ui::icon name="code"/>
            <x-ui::alert.title>Undocumented code</x-ui::alert.title>
            <x-ui::alert.description>This function has not been documented yet.</x-ui::alert.description>
        </x-ui::alert>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->filters(...)
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Intended Route</x-ui::title>
        <x-ui::text><x-ui::code>intendedRoute(...)</x-ui::code></x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->intendedRoute('resources.nodes.show', ['node' => '{id}'])
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="2xl">Props</x-ui::title>
        <x-ui::text><x-ui::code>props(...)</x-ui::code></x-ui::text>

        <x-ui::alert variant="warning">
            <x-ui::icon name="code"/>
            <x-ui::alert.title>Undocumented code</x-ui::alert.title>
            <x-ui::alert.description>This function has not been documented yet.</x-ui::alert.description>
        </x-ui::alert>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Table;

                    public function index(): Table
                    {
                        return Table::make()
                            ->props(...)
                            ->columns([
                                // ...
                            ]);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
