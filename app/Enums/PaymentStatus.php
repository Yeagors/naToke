<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::Confirmed => 'Зачислено',
            self::Failed => 'Ошибка',
            self::Cancelled => 'Отменён',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Confirmed, self::Failed, self::Cancelled], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-driver',
            self::Confirmed => 'badge-deposit',
            self::Failed, self::Cancelled => 'badge-withdrawal',
        };
    }
}
