{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Pages Overview - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Pages</x-ui::teaser>
            <x-ui::title>Overview</x-ui::title>
        </div>
    </x-ui::heading>

    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Experimental feature ahead!</x-ui::alert.title>
        <x-ui::alert.description>The page builder is currently in development and not final, methods may change over time.</x-ui::alert.description>
    </x-ui::alert>

    <x-ui::space.y>
        <x-ui::title size="2xl">What a Page Schema Does</x-ui::title>
        <x-ui::text>Page schemas orchestrate read-focused experiences such as dashboards and resource detail views. They combine layout components, server-side data fetching, and contextual actions so resources like <x-ui::code>TenantResource</x-ui::code> can deliver a cohesive overview.</x-ui::text>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Key Building Blocks</x-ui::title>
        <x-ui::space.y>
            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Meta &amp; Context</x-ui::card.title>
                    <x-ui::card.description>Provide the information that powers breadcrumbs, headers, and initial state.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text><x-ui::code>title()</x-ui::code>, <x-ui::code>description()</x-ui::code>, and <x-ui::code>teaser()</x-ui::code> keep the header aligned with the navigation. Use <x-ui::code>props()</x-ui::code> to pass computed values into the Vue components rendered within the page schema.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>

            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Schema Layout</x-ui::card.title>
                    <x-ui::card.description>Arrange blocks such as tabs, relations, or embedded forms.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>Use the <x-ui::code>Pages\Components</x-ui::code> collection. <x-ui::code>Tabs</x-ui::code> group related information, <x-ui::code>Relation</x-ui::code> renders tables inside the page, <x-ui::code>Form</x-ui::code> embeds form schemas, and <x-ui::code>Placeholder</x-ui::code> displays simple values while reusing the design language.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>

            <x-ui::card>
                <x-ui::card.header>
                    <x-ui::card.title>Actions &amp; Navigation</x-ui::card.title>
                    <x-ui::card.description>Guide the reader to the next meaningful interaction.</x-ui::card.description>
                </x-ui::card.header>
                <x-ui::card.content>
                    <x-ui::text>Attach contextual call-to-actions via <x-ui::code>Pages\Actions\Action</x-ui::code>. Pair <x-ui::code>fetch()</x-ui::code> with <x-ui::code>intendedRoute()</x-ui::code> so buttons and deep links remain in sync with the resource workflow.</x-ui::text>
                </x-ui::card.content>
            </x-ui::card>
        </x-ui::space.y>
    </x-ui::space.y>

    <x-ui::space.y>
        <x-ui::title size="xl">Implementation Tips</x-ui::title>
        <x-ui::alert variant="info">
            <x-ui::icon name="sparkles"/>
            <x-ui::alert.description>
                Keep schemas small and composable. Extract reusable tab or relation builders to dedicated methods when serving multiple resources (for example <x-ui::code>NodeResource</x-ui::code> and <x-ui::code>UserResource</x-ui::code>) to ensure consistent UX and make testing easier.
            </x-ui::alert.description>
        </x-ui::alert>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
