<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Froxlor\Core\Support\SettingRegistry;
use LogicException;
use Tests\TestCase;

class SettingRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SettingModel::query()->where('category', 'tests-registry')->delete();
    }

    protected function tearDown(): void
    {
        SettingModel::query()->where('category', 'tests-registry')->delete();

        parent::tearDown();
    }

    public function test_setting_paths_cannot_be_registered_by_two_packages(): void
    {
        Setting::add('tests-registry.collision', 'first', source: 'tests/package-a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is owned by "tests/package-a" and cannot be registered by "tests/package-b"');

        Setting::add('tests-registry.collision', 'second', source: 'tests/package-b');
    }

    public function test_same_package_can_register_a_definition_idempotently(): void
    {
        $first = Setting::add('tests-registry.idempotent', 'first', source: 'tests/package-a');
        $second = Setting::add('tests-registry.idempotent', 'second', source: 'tests/package-a');

        $this->assertSame($first->definition_key, $second->definition_key);
        $this->assertSame('tests/package-a', $second->owner_package);
        $this->assertSame('second', $second->value);
    }

    public function test_only_the_owner_can_rename_a_setting_definition(): void
    {
        Setting::add('tests-registry.old_name', 'value', source: 'tests/package-a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is owned by "tests/package-a" and cannot be renamed by "tests/package-b"');

        SettingRegistry::rename('tests-registry.old_name', 'tests-registry.new_name', 'tests/package-b');
    }

    public function test_owner_rename_keeps_the_definition_identity(): void
    {
        $setting = Setting::add('tests-registry.old_name', 'value', source: 'tests/package-a');

        SettingRegistry::rename('tests-registry.old_name', 'tests-registry.new_name', 'tests/package-a');

        $renamed = SettingModel::query()
            ->where('category', 'tests-registry')
            ->where('key', 'new_name')
            ->firstOrFail();

        $this->assertSame($setting->definition_key, $renamed->definition_key);
        $this->assertSame('tests/package-a', $renamed->owner_package);
        $this->assertDatabaseMissing('settings', [
            'category' => 'tests-registry',
            'key' => 'old_name',
        ]);
    }

    public function test_direct_definition_metadata_updates_are_rejected(): void
    {
        $setting = Setting::add('tests-registry.immutable', 'value', source: 'tests/package-a');
        $setting->key = 'changed';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Setting definition "tests-registry.changed" is immutable');

        $setting->save();
    }
}
