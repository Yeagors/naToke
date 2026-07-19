<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Battery extends Model
{
    protected $fillable = [
        'car_model',
        'capacity',
        'vin',
        'callsign',
        'comment',
    ];

    public function rentals(): BelongsToMany
    {
        return $this->belongsToMany(Rental::class);
    }

    /** Батарея занята, если есть открытая или приостановленная аренда с ней. */
    public function activeRentals(): BelongsToMany
    {
        return $this->belongsToMany(Rental::class)
            ->whereIn('rentals.status', [RentalStatus::Open->value, RentalStatus::Paused->value]);
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
