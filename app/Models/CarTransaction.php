<?php

namespace App\Models;

use App\Enums\CarTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarTransaction extends Model
{
    protected $fillable = [
        'car_id',
        'rental_id',
        'type',
        'amount',
        'balance_after',
        'comment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CarTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignedAmountAttribute(): string
    {
        $sign = $this->type === CarTransactionType::Income ? '+' : '-';
        return $sign . number_format((float) $this->amount, 2, '.', ' ');
    }
}
