<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Support\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Baseline settings required for every froxlor installation.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $settings = [
        ['category' => 'auditlog', 'key' => 'enabled', 'value' => true, 'default_value' => true, 'type' => 'boolean'],
        ['category' => 'api', 'key' => 'pagination_limit', 'value' => 15, 'default_value' => 15, 'type' => 'integer'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        foreach ($this->settings as $setting) {
            Setting::addFromArray($setting);
        }
    }
}
