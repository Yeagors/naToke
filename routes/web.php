<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\CarTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::middleware('admin')->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/search.json', [UserController::class, 'search'])->name('users.search');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/transactions', [TransactionController::class, 'store'])->name('transactions.store');

        // Cars
        Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
        Route::get('/cars/create', [CarController::class, 'create'])->name('cars.create');
        Route::post('/cars', [CarController::class, 'store'])->name('cars.store');
        Route::get('/cars/{car}', [CarController::class, 'show'])->name('cars.show');
        Route::patch('/cars/{car}', [CarController::class, 'update'])->name('cars.update');
        Route::post('/cars/{car}/transactions', [CarTransactionController::class, 'store'])->name('cars.transactions.store');
        Route::post('/cars/{car}/rentals', [RentalController::class, 'store'])->name('cars.rentals.store');

        // Tariffs
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('/tariffs/create', [TariffController::class, 'create'])->name('tariffs.create');
        Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
        Route::get('/tariffs/{tariff}', [TariffController::class, 'show'])->name('tariffs.show');
        Route::patch('/tariffs/{tariff}', [TariffController::class, 'update'])->name('tariffs.update');

        // Rentals
        Route::get('/rentals/{rental}', [RentalController::class, 'show'])->name('rentals.show');
        Route::post('/rentals/{rental}/pause', [RentalController::class, 'pause'])->name('rentals.pause');
        Route::post('/rentals/{rental}/resume', [RentalController::class, 'resume'])->name('rentals.resume');
        Route::post('/rentals/{rental}/close', [RentalController::class, 'close'])->name('rentals.close');
    });
});

require __DIR__.'/auth.php';
