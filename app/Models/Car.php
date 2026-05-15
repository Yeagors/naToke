<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class)->latest();
    }

    public function activeRental(): HasOne
    {
        return $this->hasOne(Rental::class)
            ->whereIn('status', [RentalStatus::Open->value, RentalStatus::Paused->value])
            ->latestOfMany();
    }

    public function carTransactions(): HasMany
    {
        return $this->hasMany(CarTransaction::class)->latest();
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
