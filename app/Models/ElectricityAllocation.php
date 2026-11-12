<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricityAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id', 'student_id', 'student_name', 'units', 'amount', 'paid_amount', 'is_paid',
    ];

    protected $casts = [
        'units' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(ElectricityBill::class, 'bill_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
