{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Forms Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Forms</x-ui::teaser>
            <x-ui::title>Components</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The form builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::text size="sm" class="text-zinc-300">All form components live in the <x-ui::code>Froxlor\UI\Forms\Components</x-ui::code> namespace. The examples below showcase typical configurations together with validation helpers.</x-ui::text>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Text Input</x-ui::title>
        <x-ui::text>Flexible single-line input with helpers for common HTML5 types.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    Forms\Components\TextInput::make('email')
                        ->label(trans('froxlor-core::generic.email'))
                        ->required()
                        ->email()
                        ->rules(['email', 'max:255']);

                    Forms\Components\TextInput::make('password')
                        ->label(trans('froxlor-core::generic.password'))
                        ->required()
                        ->password();

                    Forms\Components\TextInput::make('url')
                        ->label('Repository URL')
                        ->url()
                        ->default(fn() => config('app.url'));
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Text Area</x-ui::title>
        <x-ui::text>Multi-line text entry for longer descriptions or notes. Use <x-ui::code>->fill(['rows' => 5])</x-ui::code> if you need a custom height.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    Forms\Components\TextArea::make('description')
                        ->label(trans('froxlor-core::generic.description'))
                        ->default('')
                        ->rules(['nullable', 'max:2000'])
                        ->fill(['rows' => 5, 'placeholder' => trans('froxlor-core::generic.description_placeholder')]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Select</x-ui::title>
        <x-ui::text>Dropdown selector that can hydrate options lazily or from arrays.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\Core\Models\Node;
                    use Froxlor\Core\Services\Node\Adapter\Local;

                    Forms\Components\Select::make('adapter')
                        ->label(trans('froxlor-core::generic.adapter'))
                        ->options(fn() => array_map(fn ($adapter) => trans($adapter::$name), Node::adapters()))
                        ->default(Local::class)
                        ->required();

                    Forms\Components\Select::make('theme')
                        ->label(trans('froxlor-ui::generic.theme'))
                        ->options([
                            'light' => trans('froxlor-ui::generic.light'),
                            'dark' => trans('froxlor-ui::generic.dark'),
                            'system' => trans('froxlor-ui::generic.system_default'),
                        ])
                        ->default('system');
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Boolean</x-ui::title>
        <x-ui::text>Toggle for true/false flags rendered in the standard froxlor switch style.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    Forms\Components\Boolean::make('sudo')
                        ->label(trans('froxlor-core::generic.sudo'))
                        ->default(false);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Color Picker</x-ui::title>
        <x-ui::text>Capture brand or accent colors with validation for proper hex values.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    Forms\Components\Color::make('brand_color')
                        ->label('Brand color')
                        ->default('#0099ff')
                        ->rules(['required', 'regex:/^#([A-Fa-f0-9]{6})$/']);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Placeholder</x-ui::title>
        <x-ui::text>Read-only output for system-generated data, often paired with timestamps.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    use Froxlor\Core\Models\User;

                    Forms\Components\Placeholder::make('created_at')
                        ->label(trans('froxlor-core::generic.created_at'))
                        ->default(fn (User $user) => $user->created_at?->diffForHumans());
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Dump</x-ui::title>
        <x-ui::text>Developer-centric helper for rendering arbitrary payloads while iterating on a schema.</x-ui::text>
        <x-ui::code.playground language="php">
            <x-slot:code>
                @verbatim
                    Forms\Components\Dump::make('debug_payload')
                        ->label('Debug payload')
                        ->default(fn () => [
                            'plan' => 'premium',
                            'features' => ['mail', 'dns', 'backups'],
                        ]);
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
