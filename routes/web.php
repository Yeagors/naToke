<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiSellerDemoController;
use App\Http\Controllers\BatteryController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CompanyTransactionController;
use App\Http\Controllers\CarTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Публичные юридические страницы — доступны без авторизации
// (нужны для оплаты и модерации T-Bank).
Route::view('/offer', 'legal.offer')->name('offer');
Route::view('/contacts', 'legal.contacts')->name('contacts');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Top-up by QR (driver + admin both can request for themselves)
    Route::post('/payments', [PaymentController::class, 'create'])->name('payments.create');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status'])->name('payments.status');
    // Fake-gateway confirmation (URL embedded in QR for simulator)
    Route::match(['get', 'post'], '/payments/{payment}/confirm-fake', [PaymentController::class, 'confirmFake'])
        ->name('payments.fake.confirm');

    Route::middleware('admin')->group(function () {
        // Company finances (owner-split income/expenses)
        Route::get('/company', [CompanyTransactionController::class, 'index'])->name('company.index');
        Route::get('/company/create', [CompanyTransactionController::class, 'create'])->name('company.create');
        Route::post('/company', [CompanyTransactionController::class, 'store'])->name('company.store');

        // SBP top-ups (admin overview + refund)
        Route::get('/sbp-payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/search.json', [UserController::class, 'search'])->name('users.search');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/users/{user}/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // Cars
        Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
        Route::get('/cars/create', [CarController::class, 'create'])->name('cars.create');
        Route::post('/cars', [CarController::class, 'store'])->name('cars.store');
        Route::get('/cars/{car}', [CarController::class, 'show'])->name('cars.show');
        Route::patch('/cars/{car}', [CarController::class, 'update'])->name('cars.update');
        Route::delete('/cars/{car}', [CarController::class, 'destroy'])->name('cars.destroy');
        Route::post('/cars/{car}/transactions', [CarTransactionController::class, 'store'])->name('cars.transactions.store');
        Route::post('/cars/{car}/rentals', [RentalController::class, 'store'])->name('cars.rentals.store');

        // Batteries (АКБ)
        Route::get('/batteries', [BatteryController::class, 'index'])->name('batteries.index');
        Route::get('/batteries/create', [BatteryController::class, 'create'])->name('batteries.create');
        Route::post('/batteries', [BatteryController::class, 'store'])->name('batteries.store');
        Route::get('/batteries/{battery}/edit', [BatteryController::class, 'edit'])->name('batteries.edit');
        Route::patch('/batteries/{battery}', [BatteryController::class, 'update'])->name('batteries.update');

        // Tariffs
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('/tariffs/create', [TariffController::class, 'create'])->name('tariffs.create');
        Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
        Route::get('/tariffs/{tariff}', [TariffController::class, 'show'])->name('tariffs.show');
        Route::patch('/tariffs/{tariff}', [TariffController::class, 'update'])->name('tariffs.update');

        // Rentals — admin only
        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/{rental}/contract', [RentalController::class, 'contract'])->name('rentals.contract');
        Route::post('/rentals/{rental}/pause', [RentalController::class, 'pause'])->name('rentals.pause');
        Route::post('/rentals/{rental}/resume', [RentalController::class, 'resume'])->name('rentals.resume');
        Route::post('/rentals/{rental}/close', [RentalController::class, 'close'])->name('rentals.close');

        // Заявки от AI-менеджера (демо-лиды)
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');

        // Activity logs (audit)
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

        // Stats / dashboard analytics
        Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
        Route::get('/stats/unit-economics', [StatsController::class, 'unitEconomics'])->name('stats.unit');

        // Monthly financial report (XLSX)
        Route::get('/reports/monthly.xlsx', [ReportController::class, 'monthly'])->name('reports.monthly');
    });

    // Rental show — accessible by admin OR the renter (ownership check inside controller)
    Route::get('/rentals/{rental}', [RentalController::class, 'show'])->name('rentals.show');
});

// ============================================================
// AI SELLER DEMO — ВРЕМЕННО. Удалить этот блок вместе с
// app/Http/Controllers/AiSellerDemoController.php и
// resources/views/ai-seller-demo.blade.php. Публичный, без auth.
// ============================================================
Route::get('/ai-seller-demo', [AiSellerDemoController::class, 'index'])->name('ai.demo');
Route::post('/ai-seller-demo/chat', [AiSellerDemoController::class, 'chat'])->name('ai.demo.chat');

// Public payment webhook from T-Bank. CSRF exempt is configured in bootstrap/app.php
// (validateCsrfTokens except 'payments/webhook/*'). Provider's signed Token is verified inside the gateway.
Route::post('/payments/webhook/tbank', [PaymentController::class, 'webhookTBank'])
    ->name('payments.webhook.tbank');

require __DIR__.'/auth.php';
