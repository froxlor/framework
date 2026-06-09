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
        Schema::create('resources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->index();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('model_type')->index();
            $table->enum('type', ['tenant', 'environment'])->default('environment');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['key', 'model_type', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
