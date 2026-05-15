<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Пополнение',
            self::Withdrawal => 'Списание',
        };
    }

    public function sign(): int
    {
        return $this === self::Deposit ? 1 : -1;
    }
}
