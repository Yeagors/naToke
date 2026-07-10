<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTransaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'comment',
        'source',
        'payment_request_id',
        'created_by',
        'splits',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'splits' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function getSignedAmountAttribute(): string
    {
        return ($this->isIncome() ? '+' : '−').number_format((float) $this->amount, 2, '.', ' ');
    }
}
