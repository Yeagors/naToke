<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'source',
        'name',
        'phone',
        'model',
        'tariff',
        'visit_at',
        'result',
        'reason',
        'summary',
        'ref',
    ];

    /** Человекочитаемый результат. */
    public function getResultLabelAttribute(): string
    {
        return match ($this->result) {
            'booked'  => 'Запись на выдачу',
            'handoff' => 'Нужен менеджер',
            'think'   => 'Думает',
            'reject'  => 'Отказ',
            default   => 'Новый',
        };
    }
}
