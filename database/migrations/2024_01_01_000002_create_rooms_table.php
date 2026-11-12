<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('floor', 20)->nullable();
            $table->enum('type', ['single', 'double', 'triple', 'four_sharing', 'custom', 'family', 'shop'])->default('single');
            $table->integer('capacity')->default(1);
            $table->decimal('rent_amount', 10, 2)->default(0);
            $table->enum('rent_type', ['per_bed', 'per_room'])->default('per_bed');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('property_id');
            $table->unique(['property_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
