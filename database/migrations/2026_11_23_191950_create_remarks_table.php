<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * remarks — technician notes on a job (PBL Stage 1, section 5.2 table 5).
 * One request can have many remarks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_id')
                ->constrained('maintenance_requests')
                ->cascadeOnDelete();

            // ON DELETE SET NULL: remarks stay on the job even if the
            // technician's account is later deleted.
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remark');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
};
