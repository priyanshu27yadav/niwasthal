<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['single', 'double', 'triple', 'four_sharing', 'custom', 'family', 'shop'];
    public const UNIT_TYPES = ['family', 'shop'];

    protected $fillable = [
        'property_id', 'room_number', 'floor', 'type',
        'capacity', 'rent_amount', 'rent_type', 'description', 'is_active',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function isUnitType(): bool
    {
        return in_array($this->type, self::UNIT_TYPES, true);
    }

    public function getRentBasisLabelAttribute(): string
    {
        return $this->rent_type === 'per_room' ? 'Per Room' : 'Per Bed';
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'room_id');
    }

    public function activeStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'room_id')->where('status', 'active');
    }

    public function pastStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'room_id')
            ->where('status', 'vacated')
            ->orderByDesc('leaving_date')
            ->orderByDesc('updated_at');
    }

    public function getOccupiedBedsCountAttribute(): int
    {
        return $this->beds()->where('is_occupied', true)->count();
    }

    public function getAvailableBedsCountAttribute(): int
    {
        return $this->beds()->where('is_occupied', false)->count();
    }

    public function getIsFullAttribute(): bool
    {
        if ($this->isUnitType() || $this->capacity === 0) {
            return $this->activeStudents()->exists();
        }

        return $this->occupied_beds_count >= $this->capacity;
    }

    public static function compareRoomNumbers(string $a, string $b): int
    {
        $aTrim = trim($a);
        $bTrim = trim($b);
        $aNum  = preg_match('/^\d+$/', $aTrim) ? (int) $aTrim : null;
        $bNum  = preg_match('/^\d+$/', $bTrim) ? (int) $bTrim : null;

        if ($aNum !== null && $bNum !== null) {
            return $aNum <=> $bNum ?: strcmp($aTrim, $bTrim);
        }
        if ($aNum !== null) {
            return -1;
        }
        if ($bNum !== null) {
            return 1;
        }

        return strnatcasecmp($aTrim, $bTrim);
    }

    public static function sortCollection(\Illuminate\Support\Collection $rooms): \Illuminate\Support\Collection
    {
        return $rooms->sort(function (self $a, self $b) {
            return ($a->property_id <=> $b->property_id)
                ?: self::compareRoomNumbers((string) $a->room_number, (string) $b->room_number);
        })->values();
    }

    public static function summarizeOccupancy(\Illuminate\Support\Collection $rooms): array
    {
        $totalBeds = 0;
        $occupiedBeds = 0;
        $totalUnits = 0;
        $occupiedUnits = 0;

        foreach ($rooms as $room) {
            if ($room->isUnitType()) {
                $totalUnits++;
                if ($room->activeStudents->isNotEmpty()) {
                    $occupiedUnits++;
                }
            } else {
                $totalBeds += $room->beds->count();
                $occupiedBeds += $room->beds->where('is_occupied', true)->count();
            }
        }

        $totalCapacity     = $totalBeds + $totalUnits;
        $occupiedCapacity  = $occupiedBeds + $occupiedUnits;
        $availableCapacity = max(0, $totalCapacity - $occupiedCapacity);
        $occupancyPercent  = $totalCapacity > 0
            ? round(($occupiedCapacity / $totalCapacity) * 100, 1)
            : 0;

        return compact(
            'totalBeds',
            'occupiedBeds',
            'totalUnits',
            'occupiedUnits',
            'totalCapacity',
            'occupiedCapacity',
            'availableCapacity',
            'occupancyPercent'
        );
    }
}
