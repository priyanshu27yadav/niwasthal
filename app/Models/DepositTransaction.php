<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['deposit_id', 'type', 'amount', 'reason', 'transaction_date', 'reference'];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(SecurityDeposit::class, 'deposit_id');
    }
}
