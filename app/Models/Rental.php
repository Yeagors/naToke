<?php

namespace App\Models;

use App\Enums\RentalStatus;
use App\Enums\TariffPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'car_id',
        'user_id',
        'tariff_id',
        'battery_id',
        'status',
        'amount',
        'period',
        'period_count',
        'deposit_amount',
        'extras',
        'is_buyout',
        'buyout_price',
        'buyout_days_total',
        'buyout_remaining',
        'buyout_days_remaining',
        'buyout_completed_at',
        'started_at',
        'next_charge_at',
        'last_charged_at',
        'paused_at',
        'closed_at',
        'comment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RentalStatus::class,
            'period' => TariffPeriod::class,
            'amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'period_count' => 'integer',
            'extras' => 'array',
            'is_buyout' => 'boolean',
            'buyout_price' => 'decimal:2',
            'buyout_days_total' => 'integer',
            'buyout_remaining' => 'decimal:2',
            'buyout_days_remaining' => 'integer',
            'buyout_completed_at' => 'datetime',
            'started_at' => 'datetime',
            'next_charge_at' => 'datetime',
            'last_charged_at' => 'datetime',
            'paused_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getBuyoutProgressPercentAttribute(): int
    {
        if (! $this->is_buyout || ! $this->buyout_price || (float) $this->buyout_price <= 0) {
            return 0;
        }
        $paid = (float) $this->buyout_price - (float) ($this->buyout_remaining ?? $this->buyout_price);
        return (int) round($paid / (float) $this->buyout_price * 100);
    }

    public function getBuyoutPaidAttribute(): float
    {
        if (! $this->is_buyout || ! $this->buyout_price) {
            return 0;
        }
        return max(0, (float) $this->buyout_price - (float) ($this->buyout_remaining ?? $this->buyout_price));
    }

    public function isBuyoutCompleted(): bool
    {
        return $this->is_buyout && $this->buyout_completed_at !== null;
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function battery(): BelongsTo
    {
        return $this->belongsTo(Battery::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function userTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function carTransactions(): HasMany
    {
        return $this->hasMany(CarTransaction::class)->latest();
    }

    public function isOpen(): bool
    {
        return $this->status === RentalStatus::Open;
    }

    public function isPaused(): bool
    {
        return $this->status === RentalStatus::Paused;
    }

    public function isClosed(): bool
    {
        return $this->status === RentalStatus::Closed;
    }

    public function computeNextChargeFrom(Carbon $base): Carbon
    {
        return $this->period->addTo($base, $this->period_count ?: 1);
    }
}
