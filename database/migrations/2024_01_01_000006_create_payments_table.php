<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            // Snapshot of student at time of payment
            $table->string('student_name');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'upi', 'bank_transfer', 'razorpay', 'cheque', 'other'])->default('cash');
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_signature')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('receipt_number')->unique();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index('student_id');
            $table->index('property_id');
            $table->index('status');
            $table->index('paid_at');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rent_record_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->index('payment_id');
            $table->index('rent_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
