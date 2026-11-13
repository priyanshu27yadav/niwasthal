<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add owner bank and payout details to users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('users', 'bank_account_holder')) {
                $table->string('bank_account_holder')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('users', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_account_holder');
            }
            if (! Schema::hasColumn('users', 'bank_ifsc')) {
                $table->string('bank_ifsc')->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('users', 'upi_id')) {
                $table->string('upi_id')->nullable()->after('bank_ifsc');
            }
            if (! Schema::hasColumn('users', 'upi_qr_image')) {
                $table->string('upi_qr_image')->nullable()->after('upi_id');
            }
            if (! Schema::hasColumn('users', 'bank_verified_at')) {
                $table->timestamp('bank_verified_at')->nullable()->after('upi_qr_image');
            }
        });

        // Add payer account info & received-in account to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_method_type')) {
                $table->string('payment_method_type')->nullable()->after('method');
            }
            if (! Schema::hasColumn('payments', 'payer_upi_id')) {
                $table->string('payer_upi_id')->nullable()->after('payment_method_type');
            }
            if (! Schema::hasColumn('payments', 'payer_phone')) {
                $table->string('payer_phone', 20)->nullable()->after('payer_upi_id');
            }
            if (! Schema::hasColumn('payments', 'payer_account_info')) {
                $table->string('payer_account_info')->nullable()->after('payer_phone');
            }
            if (! Schema::hasColumn('payments', 'received_to_account')) {
                $table->string('received_to_account')->nullable()->after('payer_account_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = ['bank_name', 'bank_account_holder', 'bank_account_number', 'bank_ifsc', 'upi_id', 'upi_qr_image', 'bank_verified_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $cols = ['payment_method_type', 'payer_upi_id', 'payer_phone', 'payer_account_info', 'received_to_account'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
