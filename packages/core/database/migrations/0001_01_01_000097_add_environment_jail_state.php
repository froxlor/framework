<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_environments', function (Blueprint $table): void {
            $table->string('jail_path')->nullable();
            $table->json('jail_manifest')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('node_environments', fn (Blueprint $table) => $table->dropColumn(['jail_path', 'jail_manifest']));
    }
};
