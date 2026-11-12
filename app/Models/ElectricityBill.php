<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectricityBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'month', 'year', 'total_units', 'rate_per_unit',
        'fixed_charges', 'total_amount', 'split_type', 'status', 'notes',
    ];

    protected $casts = [
        'total_units' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'fixed_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ElectricityAllocation::class, 'bill_id');
    }

    public function getMonthYearAttribute(): string
    {
        return date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year));
    }
}
