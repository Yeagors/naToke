<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'balance' => (float) $user->balance,
            'recent_transactions' => $user->transactions()->take(5)->get(),
        ];

        if ($user->isAdmin()) {
            $stats['users_count'] = User::count();
            $stats['cars_count'] = Car::count();
            $stats['transactions_count'] = Transaction::count();
            $stats['total_balance'] = (float) User::sum('balance');
            $stats['total_fleet_balance'] = (float) Car::sum('balance');
            $stats['latest_users'] = User::latest()->take(5)->get();
        }

        return view('dashboard', compact('stats'));
    }
}
