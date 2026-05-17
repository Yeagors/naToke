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
        //
    }
}
