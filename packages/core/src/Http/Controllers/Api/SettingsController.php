<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Setting;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\Response;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ?string $resource = null, ?string $resource_id = null)
    {
        // Gate::authorize('viewAny', AuditLog::class);

        return Response::jsonResourceCollection(
            $this->settingsQuery($resource, $resource_id)
                ->orderBy('category')
                ->orderBy('key')
        );
    }

    public function store(Request $request, ?string $resource = null, ?string $resource_id = null)
    {
        $settings = $this->settingsQuery($resource, $resource_id)
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        foreach ($settings as $setting) {
            if (($setting->properties['readonly'] ?? false) === true) {
                continue;
            }

            $fieldKey = $this->fieldKey($setting);

            if (!$request->exists($fieldKey)) {
                continue;
            }

            $value = $this->castIncomingValue($request->input($fieldKey), $setting, $fieldKey);

            $this->validateIncomingValue($value, $setting, $fieldKey);

            $updated = $setting->update([
                'value' => $value,
            ]);

            if ($updated && $setting->wasChanged('value')) {
                $settingable = $setting->settingable;
                $tenant = match (true) {
                    $settingable instanceof Tenant => $settingable,
                    $settingable instanceof Environment => $settingable->tenant,
                    $settingable instanceof Node => $settingable->tenant,
                    default => null,
                };
                $environment = $settingable instanceof Environment ? $settingable : null;

                Audit::info('setting "' . $setting->category . '.' . $setting->key . '" updated', $tenant, $environment, [
                    'setting_id' => $setting->id,
                    'settingable_type' => $setting->settingable_type,
                    'settingable_id' => $setting->settingable_id,
                ]);
            }
        }

        return Response::jsonResource([
            'saved' => true,
        ]);
    }

    private function settingsQuery(?string $resource = null, ?string $resource_id = null)
    {
        $settings = Setting::query()
            ->where(fn ($q) => $q->whereNull('properties->visible')->orWhere('properties->visible', true));

        if (empty($resource)) {
            return $settings->whereNull('settingable_type');
        }

        $resourceFqcn = Relation::getMorphedModel($resource);
        if (empty($resourceFqcn)) {
            abort(404, 'Given resource-type could not be found');
        }

        $settings->where('settingable_type', $resourceFqcn);

        if (empty($resource_id)) {
            return $settings->whereNull('settingable_id');
        }

        if (!$resourceFqcn::query()->where('id', $resource_id)->exists()) {
            abort(404, 'Given resource could not be found');
        }

        return $settings->where('settingable_id', $resource_id);
    }

    private function fieldKey(Setting $setting): string
    {
        return 'setting_' . $setting->id;
    }

    private function castIncomingValue(mixed $value, Setting $setting, string $fieldKey): mixed
    {
        return match (strtolower((string) $setting->type)) {
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'int', 'integer' => $value === null || $value === '' ? null : (int) $value,
            'float', 'double', 'decimal', 'number' => $value === null || $value === '' ? null : (float) $value,
            'array', 'json' => $this->decodeJsonValue($value, $fieldKey),
            default => $value,
        };
    }

    /**
     * Enforce the constraints a setting declares in its properties column:
     * `options` (allowed values), `min`/`max` (numeric bounds) and, for the
     * color type, a hex color format.
     */
    private function validateIncomingValue(mixed $value, Setting $setting, string $fieldKey): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $options = $setting->properties['options'] ?? null;
        if (is_array($options) && (!is_scalar($value) || !array_key_exists((string) $value, $options))) {
            throw ValidationException::withMessages([
                $fieldKey => trans('froxlor-core::settings.invalid_option'),
            ]);
        }

        if (is_int($value) || is_float($value)) {
            $min = $setting->properties['min'] ?? null;
            $max = $setting->properties['max'] ?? null;

            if ((is_numeric($min) && $value < $min) || (is_numeric($max) && $value > $max)) {
                throw ValidationException::withMessages([
                    $fieldKey => trans('froxlor-core::settings.out_of_range', [
                        'min' => $min ?? '-∞',
                        'max' => $max ?? '∞',
                    ]),
                ]);
            }
        }

        if (strtolower((string) $setting->type) === 'color' && !preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', (string) $value)) {
            throw ValidationException::withMessages([
                $fieldKey => trans('froxlor-core::settings.invalid_color'),
            ]);
        }
    }

    private function decodeJsonValue(mixed $value, string $fieldKey): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        try {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                $fieldKey => trans('froxlor-core::settings.invalid_json'),
            ]);
        }
    }
}
