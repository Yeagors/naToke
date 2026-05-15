<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarTransaction;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $today = $now->copy()->startOfDay();

        // ---- KPI: Accounts receivable ----------------------------------------
        $receivable = abs((float) User::where('balance', '<', 0)->sum('balance'));
        $debtorsCount = User::where('balance', '<', 0)->count();

        // ---- Revenue: car_transactions.income tied to a rental ---------------
        $revenueRows = CarTransaction::query()
            ->where('type', 'income')
            ->whereNotNull('rental_id')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // ---- Cars total -----------------------------------------------------
        $totalCars = Car::count();

        // ---- Build daily series for the whole month -------------------------
        $revenueByDay = [];
        $daysSeries = [];
        $totalRevenue = 0.0;

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $key = $cursor->toDateString();
            $isPast = $cursor->lte($today);
            $isToday = $cursor->isSameDay($today);

            // Revenue
            $rev = $isPast ? (float) ($revenueRows[$key] ?? 0) : 0.0;
            $totalRevenue += $rev;
            $revenueByDay[] = [
                'date' => $key,
                'label' => $cursor->format('d.m'),
                'amount' => round($rev, 2),
                'is_past' => $isPast,
                'is_today' => $isToday,
            ];

            // Idle / rented for this day — only count past + today
            if ($isPast) {
                $dayEnd = $cursor->copy()->endOfDay();
                $dayStart = $cursor->copy()->startOfDay();
                $rentedCount = Rental::where('started_at', '<=', $dayEnd)
                    ->where(function ($q) use ($dayStart) {
                        $q->whereNull('closed_at')
                            ->orWhere('closed_at', '>=', $dayStart);
                    })
                    ->distinct('car_id')
                    ->count('car_id');
            } else {
                $rentedCount = 0;
            }

            $idleCount = $isPast ? max(0, $totalCars - $rentedCount) : 0;

            $daysSeries[] = [
                'date' => $key,
                'label' => $cursor->format('d.m'),
                'rented' => $rentedCount,
                'idle' => $idleCount,
                'is_past' => $isPast,
                'is_today' => $isToday,
            ];

            $cursor->addDay();
        }

        // ---- Today's snapshot (live) ----------------------------------------
        $todayDayEnd = $now->copy()->endOfDay();
        $todayDayStart = $now->copy()->startOfDay();
        $rentedNow = Rental::where('started_at', '<=', $todayDayEnd)
            ->where(function ($q) use ($todayDayStart) {
                $q->whereNull('closed_at')->orWhere('closed_at', '>=', $todayDayStart);
            })
            ->distinct('car_id')
            ->count('car_id');
        $idleNow = max(0, $totalCars - $rentedNow);

        // Some extra colour: best day of the month
        $bestDay = collect($revenueByDay)->sortByDesc('amount')->first();

        return view('stats.index', compact(
            'receivable',
            'debtorsCount',
            'totalRevenue',
            'totalCars',
            'rentedNow',
            'idleNow',
            'revenueByDay',
            'daysSeries',
            'monthStart',
            'monthEnd',
            'bestDay',
            'now'
        ));
    }
}
