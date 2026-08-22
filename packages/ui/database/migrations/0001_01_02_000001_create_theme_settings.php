<?php

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Appearance settings are individual rows in the settings table; the generic
     * settings UI (Froxlor\Core\Resources\Settings\SettingResource) renders them
     * from their type and properties columns, so no dedicated appearance page,
     * controller or schema exists for them.
     */
    private const BASE_COLORS = [
        'color-primary' => '#1a83b6',
        'color-primary-50' => '#e1f4fa',
        'color-primary-100' => '#b3e3f1',
        'color-primary-200' => '#84d0e9',
        'color-primary-300' => '#5abde0',
        'color-primary-400' => '#3eb0db',
        'color-primary-500' => '#29a2d6',
        'color-primary-600' => '#2395c8',
        'color-primary-700' => '#1a83b6',
        'color-primary-800' => '#1872a2',
        'color-primary-900' => '#0e5380',
        'color-primary-foreground' => '#F0F8FF',
        'color-secondary' => '#3f3f46',
        'color-secondary-foreground' => '#ffffff',
        'color-accent' => '#1a83b6',
        'color-accent-foreground' => '#F0F8FF',
        'color-card' => '#FCFCFC',
        'color-card-foreground' => '#18181B',
        'color-muted' => '#52525C', // FIXME: not final
        'color-muted-foreground' => '#52525C',
        'color-info' => '#1d4ed8',
        'color-info-foreground' => '#ffffff',
        'color-success' => '#059669',
        'color-success-foreground' => '#ffffff',
        'color-warning' => '#fbbf24',
        'color-warning-foreground' => '#92400e',
        'color-danger' => '#e11d48',
        'color-danger-foreground' => '#9f1239',
    ];

    private const DARK_COLORS = [
        'color-card' => '#27272A',
        'color-card-foreground' => '#F4F4F5',
        'color-muted' => '#9F9FA9', // FIXME: not final
        'color-muted-foreground' => '#9F9FA9',
        'color-info' => '#3b82f6',
        'color-info-foreground' => '#9F9FA9',
    ];

    /** Lightness targets used by the "generate shades" action on the primary color. */
    private const PRIMARY_SHADES = [
        'colors.base.color-primary-50' => 95,
        'colors.base.color-primary-100' => 90,
        'colors.base.color-primary-200' => 80,
        'colors.base.color-primary-300' => 70,
        'colors.base.color-primary-400' => 60,
        'colors.base.color-primary-500' => 50,
        'colors.base.color-primary-600' => 42,
        'colors.base.color-primary-700' => 35,
        'colors.base.color-primary-800' => 28,
        'colors.base.color-primary-900' => 20,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::add(
            path: 'appearance.theme',
            value: 'system',
            default: 'system',
            type: 'string',
            properties: [
                'label' => 'Theme',
                'options' => [
                    'light' => 'Light',
                    'dark' => 'Dark',
                    'system' => 'System default',
                ],
                'sort' => 10,
            ],
        );

        $this->addColors(self::BASE_COLORS, 'colors.base', 'base_colors', 100);
        $this->addColors(self::DARK_COLORS, 'colors.dark', 'dark_colors', 200);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SettingModel::query()
            ->where('category', 'appearance')
            ->whereNull('settingable_type')
            ->delete();
    }

    private function addColors(array $colors, string $keyPrefix, string $group, int $sort): void
    {
        foreach ($colors as $name => $color) {
            Setting::add(
                path: "appearance.{$keyPrefix}.{$name}",
                value: $color,
                default: $color,
                type: 'color',
                properties: array_filter([
                    'label' => Str::of($name)->after('color-')->headline()->toString(),
                    'group' => $group,
                    'sort' => $sort++,
                    'shades' => $name === 'color-primary' ? self::PRIMARY_SHADES : null,
                ]),
            );
        }
    }
};
