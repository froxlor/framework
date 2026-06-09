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
        Schema::create('domain_vhosts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('node_id')->constrained()->cascadeOnDelete();
            // web properties
            $table->string('documentroot');
            $table->boolean('access_log')->default(true);
            $table->boolean('error_log')->default(true);
            $table->enum('alias_mode', ['none', 'www', 'wildcard'])->default('wildcard'); // formerly iswildcarddomain + wwwserveralias
            // web custom-vhost properties
            $table->boolean('notryfiles')->default(false);
            $table->text('custom_vhost')->nullable();
//            $table->text('custom_ssl_vhost')->nullable();
//            $table->boolean('include_custom_vhost_in_ssl')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_vhosts');
    }
};
