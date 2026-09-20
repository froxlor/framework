<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises the real froxlor/example package's add_style_to_example_visits_table migration,
 * which needs example.greeting_style set first (see FroxlorExampleServiceProvider::
 * ensureGreetingStyleConfigured()) — i.e. the pre-migration settings gate end to end, not a
 * stand-in fake package.
 */
class PackagesSyncPreMigrationTest extends TestCase
{
    private const TEST_PACKAGE = 'froxlor/example';

    private const MIGRATION = '0001_01_01_000002_add_style_to_example_visits_table';

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetMigrationState();
        $this->clearGreetingStyleSetting();
    }

    protected function tearDown(): void
    {
        // Whatever a given test left mid-way through, always finish in a fully configured,
        // fully migrated state — other test files also call completeCompletion() on this same
        // real package and don't expect its migration to be pending, regardless of run order.
        Setting::set('example.greeting_style', Setting::get('example.greeting_style', 'casual'), 'string', source: self::TEST_PACKAGE);
        Artisan::call('migrate', ['--force' => true]);
        app(PackageService::class)->findProvider(self::TEST_PACKAGE)?->completeCompletion();

        parent::tearDown();
    }

    public function test_sync_defers_migrations_when_a_required_setting_is_missing(): void
    {
        Artisan::call('froxlor:packages:sync', ['--updated' => [self::TEST_PACKAGE]]);

        $this->assertFalse(Schema::hasColumn('example_visits', 'style'));

        $pending = app(PackageService::class)->findProvider(self::TEST_PACKAGE)->pendingCompletion();

        $this->assertNotNull($pending);
        $this->assertSame('updating', $pending['stage']);
        $this->assertSame('example.update', $pending['route']);
    }

    public function test_completing_the_gate_runs_the_deferred_migration_and_fires_updated(): void
    {
        Artisan::call('froxlor:packages:sync', ['--updated' => [self::TEST_PACKAGE]]);
        $this->assertFalse(Schema::hasColumn('example_visits', 'style'));

        Setting::set('example.greeting_style', 'casual', 'string', source: self::TEST_PACKAGE);
        SettingModel::query()->where('category', 'example')->where('key', 'last_updated_at')->delete();

        app(PackageService::class)->findProvider(self::TEST_PACKAGE)->completeCompletion();

        $this->assertTrue(Schema::hasColumn('example_visits', 'style'));
        $this->assertSame('casual', DB::table('example_visits')->value('style'));
        $this->assertNotNull(Setting::get('example.last_updated_at'));
        $this->assertNull(app(PackageService::class)->findProvider(self::TEST_PACKAGE)->pendingCompletion());
    }

    public function test_sync_runs_migrations_immediately_when_the_setting_is_already_set(): void
    {
        Setting::set('example.greeting_style', 'formal', 'string', source: self::TEST_PACKAGE);

        Artisan::call('froxlor:packages:sync', ['--updated' => [self::TEST_PACKAGE]]);

        $this->assertTrue(Schema::hasColumn('example_visits', 'style'));
        $this->assertSame('formal', DB::table('example_visits')->value('style'));
        $this->assertNull(app(PackageService::class)->findProvider(self::TEST_PACKAGE)->pendingCompletion());
    }

    private function resetMigrationState(): void
    {
        if (Schema::hasColumn('example_visits', 'style')) {
            Schema::table('example_visits', function (Blueprint $table) {
                $table->dropColumn('style');
            });
        }

        DB::table('example_visits')->delete();
        DB::table('migrations')->where('migration', self::MIGRATION)->delete();
    }

    private function clearGreetingStyleSetting(): void
    {
        SettingModel::query()
            ->where('category', 'example')
            ->where('key', 'greeting_style')
            ->delete();
    }
}
