<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Driver => 'Водитель',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
