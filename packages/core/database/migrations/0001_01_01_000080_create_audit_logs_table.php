<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->nullableUlidMorphs('auditable');
            $table->ulid('tenant_id')->nullable()->index();
            $table->ulid('environment_id')->nullable()->index();
            $table->text('action');
            $table->jsonb('context');
            $table->timestamps();
            $table->softDeletes();

            // explicitly no references to keep all audit-relevant information, tenants or environments might not exist!
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
