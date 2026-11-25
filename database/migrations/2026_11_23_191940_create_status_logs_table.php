<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * status_logs — audit trail of every status change (PBL Stage 1, section 5.2 table 4).
 *
 * Shared table: written by the Admin module (status override) and the
 * Technician module (progress updates).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();

            // Deleting a request removes its history.
            $table->foreignId('maintenance_request_id')
                ->constrained('maintenance_requests')
                ->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('status');

            // Who made the change (admin or technician).
            // ON DELETE SET NULL: if that user account is deleted, the audit
            // history is kept and shown as "Unknown user".
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['maintenance_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};
