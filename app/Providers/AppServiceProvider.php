<?php

namespace App\Providers;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager(
                $app,
                config('payments', []),
            );
        });
    }

    public function boot(): void
    {
        // Force Russian locale on Carbon (so isoFormat/translatedFormat/diffForHumans
        // produce Russian strings everywhere — web requests, queue jobs, scheduled commands).
        \Carbon\Carbon::setLocale(config('app.locale', 'ru'));
    }
}
