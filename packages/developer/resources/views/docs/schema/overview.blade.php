{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Schema Overview - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Schemas</x-ui::teaser>
            <x-ui::title>Overview</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The schema builders are currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">Building Blocks</x-ui::title>
        <x-ui::text>The froxlor UI kit ships three schema builders that cover the most common application surfaces:</x-ui::text>
        <ul class="list-disc pl-6 text-sm text-zinc-300 space-y-1">
            <li><span class="font-medium">Forms</span> – Compose create/edit flows with validation, sections, and reusable input components.</li>
            <li><span class="font-medium">Pages</span> – Assemble read-focused layouts with tabs, relations, and inline forms.</li>
            <li><span class="font-medium">Tables</span> – Define resource listings with sorting, actions, and optional redirects.</li>
        </ul>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Typical Resource Flow</x-ui::title>
        <x-ui::text><x-ui::code>Resource</x-ui::code> classes return the appropriate schema builder for each endpoint. A standard CRUD resource will expose:</x-ui::text>
        <ul class="list-disc pl-6 text-sm text-zinc-300 space-y-1">
            <li><span class="font-medium">index()</span> – returns a <x-ui::code>Table</x-ui::code> schema.</li>
            <li><span class="font-medium">create()</span> / <span class="font-medium">edit()</span> – return a <x-ui::code>Form</x-ui::code> schema.</li>
            <li><span class="font-medium">show()</span> – returns a <x-ui::code>Page</x-ui::code> schema.</li>
        </ul>
        <x-ui::text size="sm" class="text-zinc-300">Each builder shares conventions for titles, descriptions, and actions, so your navigation and breadcrumbs stay aligned.</x-ui::text>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Next Steps</x-ui::title>
        <x-ui::text>Jump into the detailed guides for the concrete builders:</x-ui::text>
        <ul class="list-disc pl-6 text-sm text-zinc-300 space-y-1">
            <li><x-ui::link href="{{ route('developers.docs', ['folder' => 'forms', 'page' => 'overview']) }}">Forms</x-ui::link> – conceptual overview, quick start, and component reference.</li>
            <li><x-ui::link href="{{ route('developers.docs', ['folder' => 'pages', 'page' => 'overview']) }}">Pages</x-ui::link> – orchestration patterns for detail screens.</li>
            <li><x-ui::link href="{{ route('developers.docs', ['folder' => 'tables', 'page' => 'overview']) }}">Tables</x-ui::link> – guidelines for consistent resource listings.</li>
        </ul>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
