<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('label', 20)->default('A'); // A, B, C, 1, 2, 3
            $table->boolean('is_occupied')->default(false);
            $table->timestamps();
            $table->index('room_id');
            $table->index('is_occupied');
            $table->unique(['room_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
