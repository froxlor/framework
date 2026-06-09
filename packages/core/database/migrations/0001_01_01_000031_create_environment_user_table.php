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
        Schema::create('environment_user', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id')->index();
            $table->ulid('user_id')->index();
            $table->ulid('role_id')->index();
            $table->ulid('plan_id')->nullable()->index();

            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles');
            // plans reference in create_plans migration

            $table->unique(['environment_id', 'user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_user');
    }
};
