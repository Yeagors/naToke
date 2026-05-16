<?php

namespace App\Models;

use App\Enums\TariffPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'amount',
        'period',
        'period_count',
        'deposit_amount',
        'extras',
        'description',
        'is_active',
        'is_buyout',
        'buyout_price',
        'buyout_days',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'period' => TariffPeriod::class,
            'period_count' => 'integer',
            'extras' => 'array',
            'is_active' => 'boolean',
            'is_buyout' => 'boolean',
            'buyout_price' => 'decimal:2',
            'buyout_days' => 'integer',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function getPeriodHumanAttribute(): string
    {
        $count = $this->period_count ?: 1;
        return $count === 1 && $count !== 0
            ? "1 {$this->period->label()}"
            : "каждые {$count} {$this->period->label()}";
    }
}
