<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::heading>
            <div>
                <x-ui::title>{{ trans('froxlor-packages::generic.discovery') }}</x-ui::title>
                <x-ui::subtitle>{{ trans('froxlor-packages::generic.discovery_description') }}</x-ui::subtitle>
            </div>

            <x-slot name="actions">
                <x-ui::button as="a" href="https://discovery.froxlor.org/packages/create" variant="secondary" icon="plus">
                    {{ trans('froxlor-packages::generic.submit_own_package') }}
                </x-ui::button>
            </x-slot>
        </x-ui::heading>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($packages as $package)
                @php($dialogName = 'package-' . $package['id'])

                <x-ui::card class="gap-0 py-0">
                    <div class="flex aspect-video w-full items-center justify-center overflow-hidden bg-zinc-100 dark:bg-white/[0.03]">
                        @if($package['image'])
                            <img src="{{ $package['image'] }}" alt="{{ $package['title'] }}" class="h-full w-full object-cover">
                        @else
                            <x-ui::icon name="package-2" size="3" class="text-zinc-300 dark:text-zinc-700"/>
                        @endif
                    </div>

                    <x-ui::card.content class="flex flex-1 flex-col space-y-3 py-6">
                        <div class="flex items-center justify-between gap-2">
                            <x-ui::card.title>{{ $package['title'] }}</x-ui::card.title>
                            <x-ui::badge :variant="$package['requires_purchase'] ? null : 'secondary'">
                                {{ $package['requires_purchase']
                                    ? number_format($package['price'], 2) . ' ' . $package['currency']
                                    : trans('froxlor-packages::generic.free') }}
                            </x-ui::badge>
                        </div>

                        <x-ui::card.description class="line-clamp-3">
                            {{ $package['description'] }}
                        </x-ui::card.description>

                        @if($package['website'])
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ preg_replace('#^https?://#i', '', $package['website']) }}
                            </div>
                        @endif
                    </x-ui::card.content>

                    <x-ui::card.footer class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t">
                        <x-ui::button
                            type="button"
                            variant="secondary"
                            size="sm"
                            x-data="{}"
                            x-on:click.prevent="$dispatch('open-dialog', '{{ $dialogName }}')"
                        >
                            {{ trans('froxlor-packages::generic.show_more') }}
                        </x-ui::button>

                        @if($package['installed'])
                            <span class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                <x-ui::icon name="check"/> {{ trans('froxlor-packages::generic.installed') }}
                            </span>
                        @elseif($package['requires_credentials'])
                            <x-ui::button as="a" href="{{ route('packages.marketplace-credentials.edit') }}" variant="secondary" icon="key" size="sm">
                                {{ trans('froxlor-packages::generic.set_credentials') }}
                            </x-ui::button>
                        @else
                            <form method="POST" action="{{ route('packages.install', ['package' => $package['id']]) }}">
                                @csrf
                                <x-ui::button type="submit" variant="primary" icon="plus" size="sm">
                                    {{ trans('froxlor-packages::generic.install') }}
                                </x-ui::button>
                            </form>
                        @endif
                    </x-ui::card.footer>
                </x-ui::card>

                <x-ui::dialog :name="$dialogName" maxWidth="lg">
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>{{ $package['title'] }}</x-ui::card.title>
                        </x-ui::card.header>
                        <x-ui::card.content
                            class="max-h-[60vh] space-y-3 overflow-y-auto text-sm text-zinc-700 dark:text-zinc-300
                                [&_h1]:mt-4 [&_h1]:mb-2 [&_h1]:text-lg [&_h1]:font-semibold
                                [&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-base [&_h2]:font-semibold
                                [&_h3]:mt-3 [&_h3]:mb-1 [&_h3]:text-sm [&_h3]:font-semibold
                                [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5
                                [&_li]:mb-1 [&_a]:text-primary [&_a]:hover:underline [&_strong]:font-semibold
                                [&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-xs [&_code]:dark:bg-white/10
                                [&_pre]:mb-3 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-zinc-100 [&_pre]:p-3 [&_pre]:dark:bg-white/5"
                        >
                            {!! $package['content_html'] !!}
                        </x-ui::card.content>
                        <x-ui::card.footer class="justify-end gap-2">
                            <x-ui::button variant="ghost" x-on:click="$dispatch('close')">
                                {{ trans('froxlor-ui::generic.cancel') }}
                            </x-ui::button>
                        </x-ui::card.footer>
                    </x-ui::card>
                </x-ui::dialog>
            @empty
                <div class="col-span-full py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ trans('froxlor-core::generic.no_entries') }}
                </div>
            @endforelse
        </div>
    </x-ui::main>
</x-ui::auth-layout>
