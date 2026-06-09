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
        Schema::create('databases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('database_server_id')->nullable()->constrained('database_servers')->nullOnDelete();
            $table->string('name');
            $table->string('database_name')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('engine')->default('mysql');
            $table->string('charset')->default('utf8mb4');
            $table->string('collation')->default('utf8mb4_unicode_ci');
            $table->string('status')->default('draft');
            $table->timestamp('provisioned_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['environment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
