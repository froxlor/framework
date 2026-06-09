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
        Schema::create('domains', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('domain')->index()->unique();
            $table->jsonb('properties')->nullable();
            $table->ulid('parent_domain_id')->nullable()->constrained('domains', 'id')->cascadeOnDelete();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('environment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
