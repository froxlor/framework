{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Forms Quick Start - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Forms</x-ui::teaser>
            <x-ui::title>Quick Start</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The form builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">Create a Resource Form</x-ui::title>
        <x-ui::text>This example mirrors the create flow used in <x-ui::code>UserResource</x-ui::code>. It wires the HTTP endpoint, groups fields, and defines footer actions.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Forms;
                    use Froxlor\UI\Forms\Form;
                    use Froxlor\UI\Schemas\Components\Section;

                    return Form::make()
                        ->title(trans('froxlor-core::generic.create_resource'))
                        ->description(trans('froxlor-core::generic.create_resource'))
                        ->push(route('api.users.store'))
                        ->intendedRoute('users.index')
                        ->schema([
                            Section::make('profile')
                                ->title('Profile')
                                ->schema([
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

                            Section::make('credentials')
                                ->title('Credentials')
                                ->schema([
                                    Forms\Components\TextInput::make('email')
                                        ->label(trans('froxlor-core::generic.email'))
                                        ->required()
                                        ->email(),

                                    Forms\Components\TextInput::make('password')
                                        ->label(trans('froxlor-core::generic.password'))
                                        ->required()
                                        ->password(),
                                ]),
                        ])
                        ->actions([
                            Forms\Actions\Action::make('back')
                                ->label(trans('froxlor-core::generic.back'))
                                ->href(route('auth.users.index')),
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Editing with the Same Schema</x-ui::title>
        <x-ui::text>Re-use the create schema to keep the UI consistent when editing. Add <x-ui::code>fetch()</x-ui::code> and adjust <x-ui::code>push()</x-ui::code> to update records in place.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    public function edit(User $user): Form
                    {
                        return $this->create()
                            ->fetch(route('api.users.show', $user))
                            ->push(route('api.users.update', $user), 'PUT')
                            ->cols(3);
                    }
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Grouping with Sections and Groups</x-ui::title>
        <x-ui::text><x-ui::code>UserResource::edit</x-ui::code> nests sections inside a group to create a two-column layout for primary and auxiliary information.</x-ui::text>

        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\UI\Schemas\Components\Group;

                    Group::make('account_overview')
                        ->schema([
                            Section::make('main_details')
                                ->title(trans('froxlor-core::generic.title'))
                                ->description('The account profile and credentials.')
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')
                                        ->label(trans('froxlor-core::generic.first_name'))
                                        ->required()
                                        ->col(3),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label(trans('froxlor-core::generic.last_name'))
                                        ->required()
                                        ->col(3),

                                    Forms\Components\TextInput::make('email')
                                        ->label(trans('froxlor-core::generic.email'))
                                        ->required()
                                        ->email(),
                                ]),
                        ])
                        ->colSpan(2);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
