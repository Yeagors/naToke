<?php

namespace App\Enums;

enum CarTransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Приход',
            self::Expense => 'Расход',
        };
    }

    public function sign(): int
    {
        return $this === self::Income ? 1 : -1;
    }
}
