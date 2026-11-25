<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            // Renamed from user_id. Deleting a resident deletes their requests.
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();

            // Deleting a technician keeps the request but unassigns it.
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Location replaces the old free-text "location" column.
            $table->foreignId('block_id')->constrained('blocks')->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('room');

            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
