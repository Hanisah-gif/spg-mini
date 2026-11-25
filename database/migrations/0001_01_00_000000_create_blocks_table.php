<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * blocks — hostel residential blocks (PBL Stage 1, section 5.2 table 1).
 *
 * Timestamp 0001_01_00 makes this run BEFORE the users migration,
 * because users.block_id references blocks.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_name', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
