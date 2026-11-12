<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electricity_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->year('year');
            $table->decimal('total_units', 10, 2)->default(0);
            $table->decimal('rate_per_unit', 8, 2)->default(0);
            $table->decimal('fixed_charges', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('split_type', ['equal', 'by_unit', 'manual'])->default('equal');
            $table->enum('status', ['draft', 'generated', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('property_id');
            $table->unique(['property_id', 'month', 'year']);
        });

        Schema::create('electricity_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('electricity_bills')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('student_name');
            $table->decimal('units', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->index('bill_id');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_allocations');
        Schema::dropIfExists('electricity_bills');
    }
};
