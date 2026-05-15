<?php

namespace App\Enums;

use Carbon\Carbon;

enum TariffPeriod: string
{
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';

    public function label(): string
    {
        return match ($this) {
            self::Minute => 'минут',
            self::Hour => 'часов',
            self::Day => 'дней',
            self::Week => 'недель',
            self::Month => 'месяцев',
        };
    }

    public function addTo(Carbon $date, int $count): Carbon
    {
        return match ($this) {
            self::Minute => $date->copy()->addMinutes($count),
            self::Hour => $date->copy()->addHours($count),
            self::Day => $date->copy()->addDays($count),
            self::Week => $date->copy()->addWeeks($count),
            self::Month => $date->copy()->addMonths($count),
        };
    }
}
