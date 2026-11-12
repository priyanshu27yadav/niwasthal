<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'property_id', 'student_name', 'room_number',
        'rent_amount', 'late_fee', 'discount', 'total_amount',
        'paid_amount', 'outstanding', 'month', 'year',
        'due_date', 'status', 'notes',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'due_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getMonthYearAttribute(): string
    {
        return date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'paid' => 'success',
            'partial' => 'warning',
            'unpaid' => 'danger',
            default => 'secondary',
        };
    }

    public function getLatestPaymentAttribute(): ?Payment
    {
        return $this->allocations
            ->pluck('payment')
            ->filter()
            ->sortByDesc('paid_at')
            ->first();
    }
}
