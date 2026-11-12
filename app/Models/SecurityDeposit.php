<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'property_id', 'student_name', 'amount', 'balance', 'collected_on', 'status', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'collected_on' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class, 'deposit_id');
    }
}
