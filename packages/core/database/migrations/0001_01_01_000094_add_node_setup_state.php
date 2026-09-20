<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->string('setup_status', 16)->nullable();
            $table->ulid('setup_request_id')->nullable();
            // Retain the actor ID for diagnosis even if the user is subsequently deleted.
            $table->ulid('setup_requested_by')->nullable();
            $table->json('setup_selection')->nullable();
            $table->char('setup_fingerprint', 64)->nullable();
            $table->ulid('setup_run_id')->nullable();
            $table->timestamp('setup_requested_at')->nullable();
            $table->timestamp('setup_started_at')->nullable();
            $table->timestamp('setup_finished_at')->nullable();
            $table->string('setup_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->dropColumn([
                'setup_status', 'setup_request_id', 'setup_requested_by', 'setup_selection',
                'setup_fingerprint', 'setup_run_id', 'setup_requested_at', 'setup_started_at',
                'setup_finished_at', 'setup_error',
            ]);
        });
    }
};
