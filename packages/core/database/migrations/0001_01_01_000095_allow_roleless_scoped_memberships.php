<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tenant_user', 'environment_user'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->ulid('role_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Never invent permissions or remove memberships to satisfy a rollback.
        foreach (['tenant_user', 'environment_user'] as $table) {
            if (DB::table($table)->whereNull('role_id')->exists()) {
                throw new RuntimeException('Assign roles to roleless memberships before rolling back this migration.');
            }
        }
        foreach (['tenant_user', 'environment_user'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->ulid('role_id')->nullable(false)->change();
            });
        }
    }
};
