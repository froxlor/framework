{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Tables Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Tables</x-ui::teaser>
            <x-ui::title>Components</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::space.y>
        <x-ui::title size="xl">TextColumn</x-ui::title>
        <x-ui::text>Helper</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Tables;

                    Tables\Columns\TextColumn::make('name')
                        ->label('froxlor-core::generic.name')
                        ->boolean()
                        ->sortable()
                        ->searchable()
                        ->toggleable()
                        ->formatValue()
                        ->html(),
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">IconColumn</x-ui::title>
        <x-ui::text>Helper</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Tables;

                    Tables\Columns\IconColumn::make('name')
                        ->label('froxlor-core::generic.name')
                        ->trueIcon('circle-check')
                        ->falseIcon('circle-x')
                        ->trueVariant('primary')
                        ->falseVariant('secondary')
                        ->boolean()
                        ->sortable()
                        ->searchable()
                        ->toggleable(),
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Next -->
</x-froxlor-developer::base-layout>
