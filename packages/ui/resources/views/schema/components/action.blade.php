<?php

use Livewire\Component;

new class extends Component
{
    public mixed $action;
}
?>

<div>
    @if($action->visible ?? true)
        @php
            $icon = $action->icon ?? null;
            $iconName = is_object($icon) ? ($icon->name ?? null) : (is_array($icon) ? ($icon['name'] ?? null) : $icon);
            $method = strtoupper((string) ($action->method ?? 'GET'));
            $confirm = $action->confirm ?? null;
            $hasConfirm = (bool) $confirm;
            $confirmData = is_array($confirm) ? $confirm : [];
            $isDestructive = (bool) ($action->destructive ?? false);
            $confirmTitle = $confirmData['title'] ?? ($action->label ?? trans($isDestructive
                ? 'froxlor-ui::generic.confirm_destructive_action_title'
                : 'froxlor-ui::generic.confirm_action_title'));
            $confirmDescription = is_string($confirm)
                ? $confirm
                : ($confirmData['description'] ?? trans($isDestructive
                    ? 'froxlor-ui::generic.confirm_destructive_action_description'
                    : 'froxlor-ui::generic.confirm_action_description'));
            $confirmLabel = $confirmData['confirm_label'] ?? ($action->label ?? trans('froxlor-ui::generic.confirm'));
            $cancelLabel = $confirmData['cancel_label'] ?? trans('froxlor-ui::generic.cancel');
            $dialogName = 'schema-action-' . md5(implode('|', [
                (string) ($action->key ?? 'action'),
                (string) ($action->href ?? ''),
                $method,
            ]));
            $formId = 'schema-action-form-' . $dialogName;
        @endphp
        @if($method === 'GET')
            @if($hasConfirm)
                <x-ui::button
                    type="button"
                    :icon="$iconName"
                    :target="$action->target ?? null"
                    :variant="$action->variant ?? 'primary'"
                    x-data="{}"
                    x-on:click.prevent="$dispatch('open-dialog', '{{ $dialogName }}')"
                >
                    {{ $action->label ?? 'NA' }}
                </x-ui::button>
                <x-ui::dialog :name="$dialogName" maxWidth="md">
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>{{ $confirmTitle }}</x-ui::card.title>
                            <x-ui::card.description>{{ $confirmDescription }}</x-ui::card.description>
                        </x-ui::card.header>
                        <x-ui::card.footer class="flex justify-end gap-2">
                            <x-ui::button variant="ghost" x-on:click="$dispatch('close')">
                                {{ $cancelLabel }}
                            </x-ui::button>
                            <x-ui::button
                                as="a"
                                :href="$action->href ?? '#'"
                                :icon="$iconName"
                                :target="$action->target ?? null"
                                :variant="$action->variant ?? 'primary'"
                            >
                                {{ $confirmLabel }}
                            </x-ui::button>
                        </x-ui::card.footer>
                    </x-ui::card>
                </x-ui::dialog>
            @else
                <x-ui::button
                    as="a"
                    :href="$action->href ?? '#'"
                    :icon="$iconName"
                    :target="$action->target ?? null"
                    :variant="$action->variant ?? 'primary'"
                >
                    {{ $action->label ?? 'NA' }}
                </x-ui::button>
            @endif
        @else
            <form id="{{ $formId }}" method="post" action="{{ $action->href ?? null }}">
                @csrf
                @method($action->method)
                @if($hasConfirm)
                    <x-ui::button
                        type="button"
                        :icon="$iconName"
                        :target="$action->target ?? null"
                        :variant="$action->variant ?? 'primary'"
                        x-data="{}"
                        x-on:click.prevent="$dispatch('open-dialog', '{{ $dialogName }}')"
                    >
                        {{ $action->label ?? 'NA' }}
                    </x-ui::button>
                @else
                    <x-ui::button :icon="$iconName" :target="$action->target ?? null" :variant="$action->variant ?? 'primary'">
                        {{ $action->label ?? 'NA' }}
                    </x-ui::button>
                @endif
            </form>
            @if($hasConfirm)
                <x-ui::dialog :name="$dialogName" maxWidth="md">
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>{{ $confirmTitle }}</x-ui::card.title>
                            <x-ui::card.description>{{ $confirmDescription }}</x-ui::card.description>
                        </x-ui::card.header>
                        <x-ui::card.footer class="flex justify-end gap-2">
                            <x-ui::button variant="ghost" x-on:click="$dispatch('close')">
                                {{ $cancelLabel }}
                            </x-ui::button>
                            <x-ui::button
                                type="button"
                                :icon="$iconName"
                                :variant="$action->variant ?? 'primary'"
                                x-on:click.prevent="document.getElementById('{{ $formId }}')?.submit()"
                            >
                                {{ $confirmLabel }}
                            </x-ui::button>
                        </x-ui::card.footer>
                    </x-ui::card>
                </x-ui::dialog>
            @endif
        @endif
   @endif
</div>
