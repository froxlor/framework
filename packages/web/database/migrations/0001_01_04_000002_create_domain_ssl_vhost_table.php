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
        Schema::create('domain_ssl_vhosts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('domain_vhost_id')->constrained()->cascadeOnDelete();
            // web-ssl properties
            $table->boolean('ssl_redirect')->default(false);
            $table->unsignedTinyInteger('ssl_mode')->default(0); // // 0 = off, 1 = auto, 2 = manual
            $table->boolean('http2')->default(true);
            $table->boolean('http3')->default(true);
            $table->boolean('hsts_enabled')->default(false);
            $table->unsignedTinyInteger('hsts_mode')->default(0); // bitwise 0 = none, 1 = sub, 2 = preload, 3 = sub + preload
            $table->unsignedTinyInteger('hsts_maxage')->default(0);
            $table->boolean('oscp_stapling')->default(false);
            // protocols and ciphers
            $table->boolean('override_tls')->default(false);
            $table->string('ssl_protocols')->nullable();
            $table->string('ssl_cipher_list')->nullable();
            $table->string('tlsv13_cipher_list')->nullable();
            $table->boolean('ssl_honorcipherorder')->default(false);
            $table->boolean('ssl_sessiontickets')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_ssl_vhosts');
    }
};
