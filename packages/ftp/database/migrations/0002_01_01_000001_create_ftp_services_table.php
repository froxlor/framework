<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ftp_services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('node_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('driver')->default('vsftpd');
            $table->string('listen_address')->default('0.0.0.0');
            $table->unsignedInteger('port')->default(21);
            $table->boolean('allow_local_users')->default(true);
            $table->boolean('allow_write')->default(true);
            $table->boolean('chroot_local_users')->default(true);
            $table->boolean('allow_writable_chroot')->default(true);
            $table->unsignedInteger('passive_min_port')->default(40000);
            $table->unsignedInteger('passive_max_port')->default(40100);
            $table->string('status')->default('defined');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('configured_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->text('last_error')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('node_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftp_services');
    }
};
