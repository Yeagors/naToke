<?php

namespace App\Enums;

enum RentalStatus: string
{
    case Open = 'open';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Открыт',
            self::Paused => 'Приостановлен',
            self::Closed => 'Закрыт',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'badge-deposit',          // green
            self::Paused => 'badge-driver',         // cyan
            self::Closed => 'badge-withdrawal',     // red
        };
    }
}
