<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->nullableUlidMorphs('settingable');
            $table->string('category')->default('general');
            $table->string('key')->index();
            $table->longText('value');
            $table->longText('default_value')->nullable();
            $table->string('type');
            $table->jsonb('properties')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['settingable_type', 'settingable_id', 'category', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
