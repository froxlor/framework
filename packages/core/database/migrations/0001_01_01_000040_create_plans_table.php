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
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->nullable()->index();
            $table->enum('type', ['tenant', 'environment'])->default('environment');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'type', 'name']);
        });

        Schema::table('environments', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans');
        });

        //Schema::table('environment_user', function (Blueprint $table) {
        //    $table->foreign('plan_id')->references('id')->on('plans');
        //});

        // foreign key for tenant_id is handled in tenants create
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
