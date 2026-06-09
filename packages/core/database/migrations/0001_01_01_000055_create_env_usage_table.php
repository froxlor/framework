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
        Schema::create('env_usage', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id')->index();
            $table->ulid('user_id')->index();
            $table->string('resource_key')->index();
            $table->ulid('resource_id')->index();
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['environment_id', 'user_id', 'resource_key', 'resource_id'], 'env_usages_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('env_usage');
    }
};
