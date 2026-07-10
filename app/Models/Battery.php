<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battery extends Model
{
    protected $fillable = [
        'car_model',
        'capacity',
        'vin',
        'callsign',
        'comment',
    ];

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /** Батарея занята, если есть открытая или приостановленная аренда с ней. */
    public function activeRentals(): HasMany
    {
        return $this->hasMany(Rental::class)
            ->whereIn('status', [RentalStatus::Open->value, RentalStatus::Paused->value]);
    }

    public function isAvailable(): bool
    {
        return ! $this->activeRentals()->exists();
    }

    /** Свободные батареи (не на активной аренде). */
    public function scopeAvailable(Builder $q): Builder
    {
        return $q->whereDoesntHave('activeRentals');
    }

    public function getLabelAttribute(): string
    {
        return trim(($this->callsign ? $this->callsign.' · ' : '').$this->car_model.' · '.$this->capacity.' · '.$this->vin);
    }
}
