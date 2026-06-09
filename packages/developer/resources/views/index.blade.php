<x-froxlor-developer::base-layout>
    <x-ui::title>Welcome to the froxlor Development Kit</x-ui::title>
    <x-ui::lead>A collection of reusable components to build fast, consistent, and modern froxlor interfaces.</x-ui::lead>

    <!-- Prerequisites -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Prerequisites</x-ui::title>
        <ul class="list-disc pl-6 text-sm text-zinc-300">
            <li>PHP 8.5+</li>
            <li>Composer</li>
            <li>Node.js and npm (for building frontend assets)</li>
        </ul>
    </x-ui::space.y>

    <!-- Publish assets -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Publish assets</x-ui::title>
        <x-ui::text>
            Publish the UI assets using the following Artisan command:
        </x-ui::text>
        <x-ui::code.pre>php artisan vendor:publish --tag=froxlor-ui-assets --ansi --force</x-ui::code.pre>
    </x-ui::space.y>

    <!-- Troubleshooting -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Troubleshooting</x-ui::title>
        <ul class="list-disc pl-6 text-sm text-zinc-300 space-y-1">
            <li>
                <span class="font-medium">Assets not loading:</span>
                Ensure you have published the assets and cleared any caches. Run <x-ui::code>php artisan optimize:clear</x-ui::code>.
            </li>
        </ul>
    </x-ui::space.y>

    <!-- Quick links -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Quick links</x-ui::title>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
            <x-ui::link href="/developers/docs/components/button">Components / Button</x-ui::link>
            <x-ui::link href="/developers/docs/components/form">Components / Form</x-ui::link>
            <x-ui::link href="/developers/docs/components/alert">Components / Alert</x-ui::link>
            <x-ui::link href="/developers/docs/forms/overview">Forms / Overview</x-ui::link>
            <x-ui::link href="/developers/docs/pages/overview">Pages / Overview</x-ui::link>
            <x-ui::link href="/developers/docs/tables/overview">Tables / Overview</x-ui::link>
        </div>
        <x-ui::text size="sm" class="text-zinc-300">Explore more in <x-ui::link href="/developers/docs/components/button">Components</x-ui::link>, <x-ui::link href="/developers/docs/layouts/app-layout">Layouts</x-ui::link>, <x-ui::link href="/developers/docs/navigations/navbar">Navigations</x-ui::link>, and <x-ui::link href="/developers/docs/utilities/schema">Utilities</x-ui::link>.</x-ui::text>
    </x-ui::space.y>

    <!-- Resources -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Resources</x-ui::title>
        <div class="space-y-1 text-sm">
            <div>
                <x-ui::link href="https://docs.froxlor.org/" target="_blank">Official Documentation</x-ui::link>
            </div>
            <div>
                <x-ui::link href="https://github.com/froxlor/framework" target="_blank">Framework Repository</x-ui::link>
            </div>
            <div>
                <x-ui::link href="https://github.com/froxlor/framework/issues" target="_blank">Issue Tracker</x-ui::link>
            </div>
            <div>
                <x-ui::link href="https://discord.froxlor.org/" target="_blank">Community Chat (Discord)</x-ui::link>
            </div>
        </div>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
