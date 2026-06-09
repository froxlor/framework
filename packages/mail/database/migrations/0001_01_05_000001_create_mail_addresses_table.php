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
        Schema::create('mail_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('domain_id')->constrained()->cascadeOnDelete();
            $table->string('address')->unique();
            $table->string('destination')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_catchall')->default(false);
            $table->decimal('spam_tag_level')->default(7.00);
            $table->boolean('rewrite_subject')->default(true);
            $table->decimal('spam_kill_level')->default(15.00);
            $table->boolean('bypass_spam')->default(false);
            $table->boolean('policy_greylist')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_addresses');
    }
};
