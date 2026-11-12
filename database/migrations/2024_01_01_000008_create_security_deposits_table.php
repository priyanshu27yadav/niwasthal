<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('student_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->date('collected_on');
            $table->enum('status', ['held', 'partially_refunded', 'refunded', 'forfeited'])->default('held');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('student_id');
            $table->index('property_id');
        });

        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained('security_deposits')->cascadeOnDelete();
            $table->enum('type', ['collect', 'deduct', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index('deposit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
        Schema::dropIfExists('security_deposits');
    }
};
