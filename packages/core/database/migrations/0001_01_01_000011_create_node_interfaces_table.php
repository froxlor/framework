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
        Schema::create('node_interfaces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('node_id')->constrained()->cascadeOnDelete();
            $table->string('bind_addr');
            $table->string('nat_addr')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['node_id', 'bind_addr']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_interfaces');
    }
};
