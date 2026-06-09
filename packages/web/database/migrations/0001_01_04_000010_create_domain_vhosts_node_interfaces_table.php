<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_vhosts_node_interfaces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('domain_vhost_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('node_interface_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('port')->default(80);
            $table->boolean('ssl_port')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['domain_vhost_id', 'node_interface_id', 'port'], 'dvni_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_vhosts_node_interfaces');
    }
};
