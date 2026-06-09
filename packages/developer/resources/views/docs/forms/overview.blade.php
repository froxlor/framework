{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Forms Overview - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Forms</x-ui::teaser>
            <x-ui::title>Overview</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The form builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">What a Form Schema Does</x-ui::title>
        <x-ui::text>Form schemas power create and edit flows for resources like <x-ui::code>UserResource</x-ui::code> and <x-ui::code>NodeResource</x-ui::code>. They bundle HTTP intentions, layout primitives, validation, and UI components into a single definition that the Vue front end renders automatically.</x-ui::text>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Key Building Blocks</x-ui::title>
        <x-ui::space.y>
            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Lifecycle &amp; Routing</x-ui::card.title>
                    <x-ui::card.description>Describe how the form communicates with the API.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>Use <x-ui::code>push()</x-ui::code> to configure the submission endpoint and HTTP verb. <x-ui::code>fetch()</x-ui::code> hydrates existing records (for edit forms) and <x-ui::code>intendedRoute()</x-ui::code> defines where to redirect on success.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>

            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Layout &amp; Grouping</x-ui::card.title>
                    <x-ui::card.description>Keep complex forms manageable by grouping fields.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>Structure inputs with <x-ui::code>Schemas\Components\Section</x-ui::code> and <x-ui::code>Group</x-ui::code>. Combine them with <x-ui::code>->colSpan()</x-ui::code> or <x-ui::code>->cols()</x-ui::code> to control responsive layouts, just like <x-ui::code>UserResource::edit</x-ui::code>.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>

            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Inputs &amp; Validation</x-ui::card.title>
                    <x-ui::card.description>Reuse opinionated UI components while enforcing business rules.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>The <x-ui::code>Forms\Components</x-ui::code> namespace contains expressive builders such as <x-ui::code>Select</x-ui::code>, <x-ui::code>TextInput</x-ui::code>, <x-ui::code>Boolean</x-ui::code>, or <x-ui::code>Color</x-ui::code>. Chain helpers like <x-ui::code>->required()</x-ui::code>, <x-ui::code>->rules()</x-ui::code>, or <x-ui::code>->default()</x-ui::code> to match your validation layer.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>

            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Footer Actions</x-ui::card.title>
                    <x-ui::card.description>Add navigation or secondary actions directly to the form footer.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>Attach buttons via <x-ui::code>Forms\Actions\Action</x-ui::code>. Typical examples include a “Back” link to the index table or a destructive action such as delete.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>
        </x-ui::space.y>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Implementation Tips</x-ui::title>
        <x-ui::alert variant="info">
            <x-ui::icon name="sparkles"/>
            <x-ui::alert.description>
                Extract reusable sections (for example credentials or connection details) into dedicated methods when you build multiple resources, such as <x-ui::code>UserResource</x-ui::code> and <x-ui::code>NodeResource</x-ui::code>. This keeps validation rules aligned and reduces translation drift.
            </x-ui::alert.description>
        </x-ui::alert>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
