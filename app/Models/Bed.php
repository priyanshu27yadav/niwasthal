<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'label', 'is_occupied'];

    protected $casts = ['is_occupied' => 'boolean'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function getLabelWithRoomAttribute(): string
    {
        return $this->room->room_number . '-' . $this->label;
    }
}
