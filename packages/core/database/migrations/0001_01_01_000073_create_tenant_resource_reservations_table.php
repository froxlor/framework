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
        Schema::create('tenant_resource_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('reserved_for_tenant_id')->index();
            $table->ulid('plan_id')->index();
            $table->string('resource_key')->index();
            $table->bigInteger('limit')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'reserved_for_tenant_id', 'resource_key'], 'tenant_resource_reservation_unique');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('reserved_for_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_resource_reservations');
    }
};
