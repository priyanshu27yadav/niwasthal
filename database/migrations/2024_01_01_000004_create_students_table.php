<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile', 15);
            $table->string('email')->nullable();
            $table->string('father_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('id_proof_type')->nullable(); // aadhar, pan, dl
            $table->string('id_proof_number')->nullable();
            $table->string('id_proof_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile', 15)->nullable();
            $table->date('joining_date');
            $table->date('leaving_date')->nullable();
            $table->decimal('monthly_rent', 10, 2)->default(0);
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'vacated'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('property_id');
            $table->index('user_id');
            $table->index('bed_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
