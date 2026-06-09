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
        Schema::create('environments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->ulid('tenant_id')->index();
            $table->ulid('plan_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // foreign key for tenant_id will be created in tenants migration
        // foreign key for plan_id will be created in plans migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
