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
        Schema::create('node_environments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('node_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('environment_id')->constrained()->cascadeOnDelete();
            $table->string('unix_name');
            $table->unsignedInteger('guid');
            $table->string('mode')->default('main');
            $table->timestamps();

            $table->unique(['node_id', 'unix_name']);
            $table->unique(['node_id', 'guid']);
            $table->unique(['node_id', 'environment_id']);
            $table->unique(['node_id', 'environment_id', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_environments');
    }
};
