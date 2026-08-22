<?php

namespace Froxlor\Core\Resources\Settings;

use Froxlor\Core\Models\Setting;
use Froxlor\UI\Forms;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class SettingResource extends Resource
{
    public function index(?string $resource = null, ?string $resource_id = null): Schema
    {
        $settings = $this->getSettings($resource, $resource_id);
        $requestedCategory = $this->requestedCategory($settings);

        return Schema::make('settings.index')
            ->title($requestedCategory ? $this->makeCategoryLabel($requestedCategory) : trans('froxlor-core::generic.settings'))
            ->description($requestedCategory
                ? trans('froxlor-core::settings.category_description', [
                    'count' => $settings->where('category', $requestedCategory)->count(),
                ])
                : trans('froxlor-core::settings.description'))
            ->push(route('api.settings.store'))
            ->intendedRoute('settings.index')
            ->actions($this->actions($requestedCategory))
            ->components($this->buildOverview($settings));
    }

    private function buildOverview(Collection $settings): array
    {
        if ($settings->isEmpty()) {
            return [
                Schemas\Components\Section::make('settings.empty')
                    ->title(trans('froxlor-core::generic.settings'))
                    ->description(trans('froxlor-core::settings.empty')),
            ];
        }

        if ($requestedCategory = $this->requestedCategory($settings)) {
            return $this->buildCategorySections($requestedCategory, $settings->where('category', $requestedCategory)->values());
        }

        return [
            Schemas\Components\Tabs::make('settings.categories')
                ->default($this->categoryTabKey((string) $settings->first()->category))
                ->overhang(false)
                ->components(
                    $settings
                        ->groupBy('category')
                        ->map(fn (Collection $categorySettings, string $category) => $this->buildCategoryTab($category, $categorySettings))
                        ->values()
                        ->all()
                ),
        ];
    }

    private function buildCategoryTab(string $category, Collection $settings): Schemas\Components\Tab
    {
        return Schemas\Components\Tab::make($this->categoryTabKey($category))
            ->label($this->makeCategoryLabel($category))
            ->components($this->buildCategorySections($category, $settings));
    }

    /**
     * A category renders as one section, or several when settings carry a
     * `properties.group` key (sections appear in first-occurrence order).
     *
     * @return array<int, Schemas\Components\Section>
     */
    private function buildCategorySections(string $category, Collection $settings): array
    {
        $fieldKeys = $settings
            ->mapWithKeys(fn (Setting $setting) => [$setting->key => $this->fieldKey($setting)])
            ->all();

        return $settings
            ->groupBy(fn (Setting $setting) => (string) ($setting->properties['group'] ?? ''))
            ->map(fn (Collection $groupSettings, string $group) => $this->buildCategorySection($category, $groupSettings, $fieldKeys, $group))
            ->values()
            ->all();
    }

    private function buildCategorySection(string $category, Collection $settings, array $fieldKeys, string $group = ''): Schemas\Components\Section
    {
        return Schemas\Components\Section::make('section_' . $category . ($group !== '' ? '_' . $group : ''))
            ->title($group !== '' ? $this->makeCategoryLabel($group) : $this->makeCategoryLabel($category))
            ->description(trans('froxlor-core::settings.category_description', [
                'count' => $settings->count(),
            ]))
            ->components(
                $settings
                    ->map(fn (Setting $setting) => $this->makeField($setting, $fieldKeys))
                    ->all()
            );
    }

    /**
     * @param array<string, string> $fieldKeys Setting key => form field key, for fields referencing sibling settings
     */
    private function makeField(Setting $setting, array $fieldKeys = []): object
    {
        $label = $setting->properties['label']
            ?? Str::of($setting->key)->replace(['.', '_', '-'], ' ')->headline()->toString();
        $fieldKey = $this->fieldKey($setting);
        $value = $setting->value ?? $setting->default_value;
        $type = strtolower((string) $setting->type);

        $field = $this->makeFieldComponent($setting, $fieldKey, $label, $value, $type, $fieldKeys);

        if (isset($setting->properties['col'])) {
            $field->col((string) $setting->properties['col']);
        }

        return $field;
    }

    private function makeFieldComponent(Setting $setting, string $fieldKey, string $label, mixed $value, string $type, array $fieldKeys): object
    {
        if (($setting->properties['readonly'] ?? false) === true) {
            return Schemas\Components\Text::make($fieldKey)
                ->label($label)
                ->default($this->normalizeTextValue($value))
                ->col(3);
        }

        if (is_array($setting->properties['options'] ?? null)) {
            return Forms\Components\Select::make($fieldKey)
                ->label($label)
                ->options($setting->properties['options'])
                ->default($value)
                ->col(3);
        }

        return match ($type) {
            'bool', 'boolean' => Forms\Components\Boolean::make($fieldKey)
                ->label($label)
                ->default((bool) $value)
                ->toggle()
                ->col(3),

            'color' => $this->makeColorField($setting, $fieldKey, $label, $value, $fieldKeys),

            'int', 'integer' => Forms\Components\TextInput::make($fieldKey)
                ->label($label)
                ->default($value)
                ->integer()
                ->col(3),

            'float', 'double', 'decimal', 'number' => Forms\Components\TextInput::make($fieldKey)
                ->label($label)
                ->default($value)
                ->numeric()
                ->col(3),

            'array', 'json', 'text' => Forms\Components\TextArea::make($fieldKey)
                ->label($label)
                ->default($this->normalizeTextValue($value))
                ->col(6),

            default => $this->makeStringField($fieldKey, $label, $value),
        };
    }

    /**
     * `properties.shades` maps sibling setting keys to a lightness percentage;
     * the sibling keys are translated to their generated form field keys so the
     * client-side shade generator can fill those inputs.
     */
    private function makeColorField(Setting $setting, string $fieldKey, string $label, mixed $value, array $fieldKeys): Forms\Components\Color
    {
        $field = Forms\Components\Color::make($fieldKey)
            ->label($label)
            ->default($value)
            ->col(3);

        if (is_array($setting->properties['shades'] ?? null)) {
            $field->generateShadesFor(
                collect($setting->properties['shades'])
                    ->mapWithKeys(fn ($lightness, string $settingKey) => [
                        ($fieldKeys[$settingKey] ?? $settingKey) => (int) $lightness,
                    ])
                    ->all()
            );
        }

        return $field;
    }

    private function makeStringField(string $fieldKey, string $label, mixed $value): object
    {
        $value = $this->normalizeTextValue($value);

        if (is_string($value) && mb_strlen($value) > 120) {
            return Forms\Components\TextArea::make($fieldKey)
                ->label($label)
                ->default($value)
                ->col(6);
        }

        return Forms\Components\TextInput::make($fieldKey)
            ->label($label)
            ->default($value)
            ->col(3);
    }

    private function getSettings(?string $resource = null, ?string $resource_id = null): Collection
    {
        $query = Setting::query()
            ->where(fn ($q) => $q->whereNull('properties->visible')->orWhere('properties->visible', true));

        if (!empty($resource)) {
            $resourceFqcn = Relation::getMorphedModel($resource);
            if (empty($resourceFqcn)) {
                abort(404, 'Given resource-type could not be found');
            }

            $query->where('settingable_type', $resourceFqcn);

            if (!empty($resource_id)) {
                if (!$resourceFqcn::query()->where('id', $resource_id)->exists()) {
                    abort(404, 'Given resource could not be found');
                }

                $query->where('settingable_id', $resource_id);
            } else {
                $query->whereNull('settingable_id');
            }
        } else {
            $query->whereNull('settingable_type');
        }

        return $query
            ->orderBy('category')
            ->orderBy('key')
            ->get()
            ->sortBy([
                fn (Setting $a, Setting $b) => strcmp((string) $a->category, (string) $b->category),
                fn (Setting $a, Setting $b) => ($a->properties['sort'] ?? PHP_INT_MAX) <=> ($b->properties['sort'] ?? PHP_INT_MAX),
                fn (Setting $a, Setting $b) => strcmp($a->key, $b->key),
            ])
            ->values();
    }

    private function fieldKey(Setting $setting): string
    {
        return 'setting_' . $setting->id;
    }

    private function actions(?string $requestedCategory): array
    {
        if (!$requestedCategory) {
            return [];
        }

        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('settings.index'))
                ->icon('arrow-left')
                ->variant('secondary'),
        ];
    }

    private function categoryTabKey(string $category): string
    {
        return 'category_' . $category;
    }

    private function requestedCategory(Collection $settings): ?string
    {
        $requestedCategory = (string) request()->query('category', '');

        if ($requestedCategory === '') {
            return null;
        }

        return $settings->contains(fn (Setting $setting) => $setting->category === $requestedCategory)
            ? $requestedCategory
            : null;
    }

    private function makeCategoryLabel(string $category): string
    {
        return Str::of($category)->replace(['.', '_', '-'], ' ')->headline()->toString();
    }

    private function normalizeTextValue(mixed $value): mixed
    {
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
