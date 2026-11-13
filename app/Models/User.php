<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'mobile', 'role', 'business_type', 'owner_id', 'password',
        'bank_name', 'bank_account_holder', 'bank_account_number', 'bank_ifsc',
        'upi_id', 'upi_qr_image', 'bank_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'bank_verified_at'  => 'datetime',
        'password'          => 'hashed',
    ];

    public function hasBankDetails(): bool
    {
        return !empty($this->bank_account_number) && !empty($this->bank_ifsc);
    }

    public function hasUpi(): bool
    {
        return !empty($this->upi_id);
    }

    public function hasQr(): bool
    {
        return !empty($this->upi_qr_image) || $this->hasUpi();
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isLodgeOwner(?Property $property = null): bool
    {
        if ($property) {
            return $property->type === 'lodge';
        }

        return $this->business_type === 'lodge'
            || $this->properties()->where('type', 'lodge')->exists();
    }

    public function occupantLabel(?Property $property = null): string
    {
        return $this->isLodgeOwner($property) ? 'Renter' : 'Student';
    }

    public function occupantLabelPlural(?Property $property = null): string
    {
        return $this->occupantLabel($property) . 's';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'owner_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }
}
