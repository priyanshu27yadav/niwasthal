<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'key', 'value'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public static function get(int $ownerId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('owner_id', $ownerId)->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(int $ownerId, string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['owner_id' => $ownerId, 'key' => $key],
            ['value' => $value]
        );
    }
}
