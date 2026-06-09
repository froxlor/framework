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
        Schema::create('database_servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('node_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('driver')->default('mysql');
            $table->string('host')->default('127.0.0.1');
            $table->unsignedInteger('port')->default(3306);
            $table->string('admin_username')->nullable();
            $table->text('admin_password')->nullable();
            $table->boolean('supports_per_environment_users')->default(true);
            $table->unsignedInteger('max_databases')->nullable();
            $table->string('status')->default('defined');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('configured_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->text('last_error')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_servers');
    }
};
