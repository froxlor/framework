<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('owner_package', 191)->nullable()->after('key');
            $table->string('definition_key', 26)->nullable()->after('owner_package');
            $table->index('owner_package');
            $table->index('definition_key');
        });

        // Existing rows are grouped into stable definitions before ownership is backfilled.
        // Rows whose original package cannot be determined remain explicitly unowned and must
        // be adopted by a deliberate package migration before their definition can change.
        DB::table('settings')
            ->select('category', 'key')
            ->distinct()
            ->get()
            ->each(function (object $path): void {
                $definitionKey = DB::table('settings')
                    ->where('category', $path->category)
                    ->where('key', $path->key)
                    ->whereNotNull('definition_key')
                    ->value('definition_key')
                    ?? (string)Str::ulid();

                DB::table('settings')
                    ->where('category', $path->category)
                    ->where('key', $path->key)
                    ->update(['definition_key' => $definitionKey]);
            });

        $knownOwners = [
            'auditlog' => 'froxlor/core',
            'api' => 'froxlor/core',
            'core' => 'froxlor/core',
            'node' => 'froxlor/core',
            'appearance' => 'froxlor/ui',
        ];

        foreach ($knownOwners as $category => $owner) {
            DB::table('settings')
                ->where('category', $category)
                ->whereNull('owner_package')
                ->update(['owner_package' => $owner]);
        }

        DB::table('settings')
            ->where('category', 'packages')
            ->whereNull('owner_package')
            ->get(['id', 'key'])
            ->each(function (object $setting): void {
                $owner = match ($setting->key) {
                    'marketplace_username', 'marketplace_token' => 'froxlor/packages',
                    default => null,
                };

                if ($owner === null && preg_match('/^(.+)\.(enabled|pending)$/', $setting->key, $matches)) {
                    $owner = $matches[1];
                }

                if ($owner !== null) {
                    DB::table('settings')
                        ->where('id', $setting->id)
                        ->update(['owner_package' => $owner]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropIndex('settings_owner_package_index');
            $table->dropIndex('settings_definition_key_index');
            $table->dropColumn(['owner_package', 'definition_key']);
        });
    }
};
