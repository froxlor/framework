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
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mail_address_id')->constrained()->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('password');
            $table->unsignedInteger('uid');
            $table->unsignedInteger('gid');
            $table->string('homedir');
            $table->string('maildir');
            $table->boolean('smtp_enabled')->default(true);
            $table->boolean('pop3_enabled')->default(true);
            $table->boolean('imap_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};
