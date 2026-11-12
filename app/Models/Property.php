<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'address', 'city', 'state', 'pincode',
        'contact_number', 'email', 'facilities', 'type',
        'total_floors', 'total_rooms', 'google_maps_url', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_floors' => 'integer',
        'total_rooms' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function rentRecords(): HasMany
    {
        return $this->hasMany(RentRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function electricityBills(): HasMany
    {
        return $this->hasMany(ElectricityBill::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function securityDeposits(): HasMany
    {
        return $this->hasMany(SecurityDeposit::class);
    }

    public function getOccupiedBedsCountAttribute(): int
    {
        return $this->rooms()->with('beds')->get()->flatMap->beds->where('is_occupied', true)->count();
    }

    public function getTotalBedsCountAttribute(): int
    {
        return $this->rooms()->with('beds')->get()->flatMap->beds->count();
    }

    public function getActiveStudentsCountAttribute(): int
    {
        return $this->students()->where('status', 'active')->count();
    }
}
