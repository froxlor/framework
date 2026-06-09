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
        Schema::create('nodes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('adapter');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('hostname');
            $table->string('username');
            $table->text('password')->nullable();
            $table->boolean('sudo');
            $table->longText('properties')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
