<?php

namespace Froxlor\Core\Database\Seeders;

use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Core\Support\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    protected array $testSettings = [
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
        foreach ($this->testSettings as $setting) {
            Setting::addFromArray($setting);
        }
    }
}
