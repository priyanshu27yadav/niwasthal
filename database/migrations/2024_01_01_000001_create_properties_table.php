<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('contact_number', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('facilities')->nullable();
            $table->string('type')->default('pg'); // pg, hostel, lodge
            $table->integer('total_rooms')->default(0);
            $table->string('google_maps_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
