<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Car extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'year',
        'balance',
        'comment',
        'battery_capacity',
        'battery_number',
        'license_plate',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'balance' => 'decimal:2',
            'battery_capacity' => 'integer',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->brand} {$this->model}");
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        return Storage::disk('public')->url($this->photo);
    }
}
