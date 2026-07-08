<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'initiated_by',
        'amount',
        'charge_amount',
        'status',
        'gateway',
        'external_id',
        'qr_payload',
        'qr_url',
        'gateway_payload',
        'comment',
        'failed_reason',
        'confirmed_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'charge_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Amount actually charged via SBP (base amount + service fee).
     * Falls back to the base amount for legacy rows created before the fee.
     */
    public function getPayableAmountAttribute(): float
    {
        return (float) ($this->charge_amount ?? $this->amount);
    }

    /** Service fee portion (charge − credited amount). */
    public function getFeeAmountAttribute(): float
    {
        return max(0, $this->payable_amount - (float) $this->amount);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::Confirmed;
    }

    public function isFake(): bool
    {
        return $this->gateway === 'fake';
    }
}
