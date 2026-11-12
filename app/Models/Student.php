<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'property_id', 'bed_id', 'room_id', 'name', 'mobile', 'email',
        'father_name', 'address', 'city', 'id_proof_type', 'id_proof_number',
        'id_proof_path', 'photo_path', 'emergency_contact_name',
        'emergency_contact_mobile', 'joining_date', 'leaving_date',
        'monthly_rent', 'security_deposit', 'status', 'notes',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function rentRecords(): HasMany
    {
        return $this->hasMany(RentRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function electricityAllocations(): HasMany
    {
        return $this->hasMany(ElectricityAllocation::class);
    }

    public function securityDeposit(): HasOne
    {
        return $this->hasOne(SecurityDeposit::class)->latest();
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function getOutstandingRentAttribute(): float
    {
        return $this->rentRecords()->whereIn('status', ['unpaid', 'partial'])->sum('outstanding');
    }

    public function getRoomNumberAttribute(): ?string
    {
        return $this->room?->room_number ?? $this->bed?->room?->room_number;
    }
}
