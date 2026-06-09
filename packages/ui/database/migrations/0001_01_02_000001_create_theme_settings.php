<?php

use Froxlor\Core\Support\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Theme Base
        Setting::add(
            path: 'ui.colors.base',
            value: [
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
                'color-primary' => '#1a83b6',
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
            ],
            type: 'array'
        );

        // Theme Dark
        Setting::add(
            path: 'ui.colors.dark',
            value: [
                'color-card' => '#27272A',
                'color-card-foreground' => '#F4F4F5',
                'color-muted' => '#9F9FA9', // FIXME: not final
                'color-muted-foreground' => '#9F9FA9',
                'color-info' => '#3b82f6',
                'color-info-foreground' => '#9F9FA9',
            ],
            type: 'array',
        );

        // Theme
        Setting::add(
            path: 'ui.theme',
            value: 'system',
        );

        // Refresh cache
        Artisan::call('view:clear');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Setting::remove('ui.theme');
        // Setting::remove('ui.theme_dark');
    }
};
