<?php

use App\Console\Commands\ExpireStalePayments;
use App\Console\Commands\ProcessRentalCharges;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rentals: try to charge active rentals every minute (Europe/Moscow).
// The command itself only fires charges that are actually due (next_charge_at <= now),
// so running every minute simply gives sub-minute precision.
Schedule::command(ProcessRentalCharges::class)
    ->everyMinute()
    ->timezone('Europe/Moscow')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Payments: cancel top-ups stuck in "pending" for more than 24h (hourly).
Schedule::command(ExpireStalePayments::class)
    ->hourly()
    ->timezone('Europe/Moscow')
    ->withoutOverlapping()
    ->onOneServer();
