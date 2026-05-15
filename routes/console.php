<?php

use App\Console\Commands\ProcessRentalCharges;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rentals: try to charge active rentals every hour at minute 0 (Europe/Moscow)
Schedule::command(ProcessRentalCharges::class)
    ->hourlyAt(0)
    ->timezone('Europe/Moscow')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
