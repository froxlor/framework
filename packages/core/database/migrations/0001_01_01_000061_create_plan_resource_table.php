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
        Schema::create('plan_resource', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('plan_id')->index();
            $table->ulid('resource_id')->index();
            $table->bigInteger('limit')->default(0);

            $table->unique(['plan_id', 'resource_id']);

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_resource');
    }
};
