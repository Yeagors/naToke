<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarTransaction;
use App\Models\Rental;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatsController extends Controller
{
    /**
     * Overview tab — KPI cards + revenue/idle-rented per day for current month.
     */
    public function index(Request $request): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $today = $now->copy()->startOfDay();

        $receivable = abs((float) User::where('balance', '<', 0)->sum('balance'));
        $debtorsCount = User::where('balance', '<', 0)->count();

        $revenueRows = CarTransaction::query()
            ->where('type', 'income')
            ->whereNotNull('rental_id')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $totalCars = Car::count();

        $revenueByDay = [];
        $daysSeries = [];
        $totalRevenue = 0.0;

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $key = $cursor->toDateString();
            $isPast = $cursor->lte($today);
            $isToday = $cursor->isSameDay($today);

            $rev = $isPast ? (float) ($revenueRows[$key] ?? 0) : 0.0;
            $totalRevenue += $rev;
            $revenueByDay[] = [
                'date' => $key,
                'label' => $cursor->format('d.m'),
                'amount' => round($rev, 2),
                'is_past' => $isPast,
                'is_today' => $isToday,
            ];

            if ($isPast) {
                $dayEnd = $cursor->copy()->endOfDay();
                $dayStart = $cursor->copy()->startOfDay();
                $rentedCount = Rental::where('started_at', '<=', $dayEnd)
                    ->where(function ($q) use ($dayStart) {
                        $q->whereNull('closed_at')->orWhere('closed_at', '>=', $dayStart);
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

        $todayDayEnd = $now->copy()->endOfDay();
        $todayDayStart = $now->copy()->startOfDay();
        $rentedNow = Rental::where('started_at', '<=', $todayDayEnd)
            ->where(function ($q) use ($todayDayStart) {
                $q->whereNull('closed_at')->orWhere('closed_at', '>=', $todayDayStart);
            })
            ->distinct('car_id')
            ->count('car_id');
        $idleNow = max(0, $totalCars - $rentedNow);

        $bestDay = collect($revenueByDay)->sortByDesc('amount')->first();

        return view('stats.index', compact(
            'receivable', 'debtorsCount', 'totalRevenue',
            'totalCars', 'rentedNow', 'idleNow',
            'revenueByDay', 'daysSeries',
            'monthStart', 'monthEnd', 'bestDay', 'now',
        ));
    }

    /**
     * Unit economics — per-car ROI, payback, expense ratios, fleet KPIs.
     */
    public function unitEconomics(Request $request): View
    {
        $now = now();
        $windowStart = $now->copy()->subDays(59)->startOfDay(); // 60-day rolling window for daily cashflow chart

        // ----- Per-car aggregates ---------------------------------------------
        $cars = Car::query()->orderByDesc('id')->get();

        $incomePerCar = CarTransaction::query()
            ->where('type', 'income')
            ->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'car_id');

        $expensePerCar = CarTransaction::query()
            ->where('type', 'expense')
            ->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'car_id');

        // active days in last 30 (any income day) — stability proxy
        $activeDaysPerCar = CarTransaction::query()
            ->where('type', 'income')
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('car_id')
            ->select('car_id', DB::raw('COUNT(DISTINCT DATE(created_at)) as days'))
            ->pluck('days', 'car_id');

        // open rentals → "in use right now"
        $openRentalCarIds = Rental::where('status', 'open')->pluck('car_id')->all();

        $perCar = $cars->map(function (Car $car) use ($incomePerCar, $expensePerCar, $activeDaysPerCar, $openRentalCarIds, $now) {
            $income = (float) ($incomePerCar[$car->id] ?? 0);
            $expense = (float) ($expensePerCar[$car->id] ?? 0);
            $net = $income - $expense;
            $purchase = $car->purchase_price !== null ? (float) $car->purchase_price : null;

            // Net minus purchase = profit since acquisition
            $profitSincePurchase = $purchase !== null ? $net - $purchase : null;

            // ROI based on purchase price (returned vs invested)
            $roiPct = ($purchase !== null && $purchase > 0)
                ? round(($net / $purchase) * 100, 1)
                : null;

            $paybackPct = $roiPct !== null ? max(0, min(100, $roiPct)) : null;
            $isPaidBack = $purchase !== null ? $net >= $purchase : null;

            $sinceDate = $car->purchase_date ?? $car->created_at;
            $daysInFleet = max(1, $sinceDate ? $sinceDate->diffInDays($now) + 1 : 1);
            $dailyAvg = $daysInFleet > 0 ? round($net / $daysInFleet, 2) : 0;
            $dailyAvgIncome = $daysInFleet > 0 ? round($income / $daysInFleet, 2) : 0;

            $activeDays30 = (int) ($activeDaysPerCar[$car->id] ?? 0);
            // utilization 30d = days with income / 30
            $util30 = (int) round($activeDays30 / 30 * 100);

            return [
                'car' => $car,
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'purchase' => $purchase,
                'profit_since_purchase' => $profitSincePurchase,
                'roi_pct' => $roiPct,
                'payback_pct' => $paybackPct,
                'is_paid_back' => $isPaidBack,
                'days_in_fleet' => $daysInFleet,
                'daily_avg_net' => $dailyAvg,
                'daily_avg_income' => $dailyAvgIncome,
                'active_days_30' => $activeDays30,
                'util_30_pct' => $util30,
                'in_use_now' => in_array($car->id, $openRentalCarIds, true),
            ];
        })->sortByDesc('net')->values();

        // ----- Fleet KPIs -----------------------------------------------------
        $kpi = [
            'investment' => $perCar->sum('purchase') ?? 0,
            'income' => $perCar->sum('income'),
            'expense' => $perCar->sum('expense'),
            'net' => $perCar->sum('net'),
        ];
        $kpi['fleet_roi_pct'] = $kpi['investment'] > 0
            ? round($kpi['net'] / $kpi['investment'] * 100, 1)
            : null;
        $kpi['paid_back_cars'] = $perCar->filter(fn ($r) => $r['is_paid_back'] === true)->count();
        $kpi['cars_with_purchase'] = $perCar->filter(fn ($r) => $r['purchase'] !== null && $r['purchase'] > 0)->count();
        $kpi['active_drivers'] = User::whereHas('rentals', fn ($q) => $q->where('status', 'open'))->count();
        $kpi['open_rentals'] = Rental::where('status', 'open')->count();
        $kpi['avg_user_balance'] = (int) round((float) User::where('role', 'driver')->avg('balance') ?? 0);

        // average rental duration (closed)
        $avgRentalDays = Rental::whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, closed_at)) / 86400 as days')
            ->value('days');
        $kpi['avg_rental_days'] = $avgRentalDays !== null ? round((float) $avgRentalDays, 1) : null;

        // ----- Daily cashflow series (60 days) --------------------------------
        $dailyIncome = CarTransaction::query()
            ->where('type', 'income')
            ->where('created_at', '>=', $windowStart)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $dailyExpense = CarTransaction::query()
            ->where('type', 'expense')
            ->where('created_at', '>=', $windowStart)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $cashflow = [];
        $cumulative = 0.0;
        $cur = $windowStart->copy();
        while ($cur->lte($now)) {
            $k = $cur->toDateString();
            $inc = (float) ($dailyIncome[$k] ?? 0);
            $exp = (float) ($dailyExpense[$k] ?? 0);
            $cumulative += ($inc - $exp);
            $cashflow[] = [
                'date' => $k,
                'label' => $cur->format('d.m'),
                'income' => round($inc, 2),
                'expense' => round($exp, 2),
                'net' => round($inc - $exp, 2),
                'cumulative' => round($cumulative, 2),
            ];
            $cur->addDay();
        }

        return view('stats.unit-economics', compact('perCar', 'kpi', 'cashflow', 'now'));
    }
}
