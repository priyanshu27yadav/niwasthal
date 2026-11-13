<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'property_id', 'student_name', 'amount', 'method',
        'payment_method_type', 'payer_upi_id', 'payer_phone', 'payer_account_info', 'received_to_account',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature',
        'transaction_reference', 'receipt_number', 'notes', 'status', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getMethodLabelAttribute(): string
    {
        if ($this->payment_method_type === 'qr_code') {
            return 'QR Code Scan';
        }
        if ($this->payment_method_type === 'upi_id') {
            return 'UPI ID / App';
        }

        return match($this->method) {
            'cash' => 'Cash',
            'upi' => 'UPI',
            'bank_transfer' => 'Bank Transfer (IMPS/NEFT)',
            'razorpay' => 'Online (Razorpay)',
            'cheque' => 'Cheque',
            default => ucfirst($this->method),
        };
    }
}
