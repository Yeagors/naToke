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
    public function index(Request $request): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $today = $now->copy()->startOfDay();

        // Дебиторка/должники — только по водителям. Балансы админов (владельцев)
        // двигаются от деления дохода/расходов компании и в задолженность не входят.
        $receivable = abs((float) User::where('role', 'driver')->where('balance', '<', 0)->sum('balance'));
        $debtorsCount = User::where('role', 'driver')->where('balance', '<', 0)->count();

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

    public function unitEconomics(Request $request): View
    {
        $now = now();
        $windowStart = $now->copy()->subDays(59)->startOfDay();
        $recent30Start = $now->copy()->subDays(29)->startOfDay();

        $cars = Car::query()->orderByDesc('id')->get();

        $incomePerCar = CarTransaction::query()->where('type', 'income')->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');
        $expensePerCar = CarTransaction::query()->where('type', 'expense')->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');

        $activeDaysPerCar = CarTransaction::query()->where('type', 'income')
            ->where('created_at', '>=', $recent30Start)->groupBy('car_id')
            ->select('car_id', DB::raw('COUNT(DISTINCT DATE(created_at)) as days'))->pluck('days', 'car_id');

        $recent30PerCar = CarTransaction::query()->where('created_at', '>=', $recent30Start)
            ->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(CASE WHEN type="income" THEN amount ELSE -amount END) as net'))
            ->get()->keyBy('car_id');

        $openRentalCarIds = Rental::where('status', 'open')->pluck('car_id')->all();

        $perCar = $cars->map(function (Car $car) use ($incomePerCar, $expensePerCar, $activeDaysPerCar, $recent30PerCar, $openRentalCarIds, $now) {
            $income = (float) ($incomePerCar[$car->id] ?? 0);
            $expense = (float) ($expensePerCar[$car->id] ?? 0);
            $net = $income - $expense;
            $purchase = $car->purchase_price !== null ? (float) $car->purchase_price : null;

            $roiPct = ($purchase !== null && $purchase > 0) ? round(($net / $purchase) * 100, 1) : null;
            $paybackPct = $roiPct !== null ? max(0, min(100, $roiPct)) : null;
            $isPaidBack = $purchase !== null ? $net >= $purchase : null;

            $sinceDate = $car->purchase_date ?? $car->created_at;
            $daysInFleet = max(1, $sinceDate ? $sinceDate->diffInDays($now) + 1 : 1);
            $dailyAvg = $daysInFleet > 0 ? round($net / $daysInFleet, 2) : 0;
            $dailyAvgIncome = $daysInFleet > 0 ? round($income / $daysInFleet, 2) : 0;

            $recentRow = $recent30PerCar->get($car->id);
            $recentDailyNet = $recentRow ? round((float) $recentRow->net / 30, 2) : 0.0;

            // ETA: how many more days at recent rate to fully pay back
            $forecastEtaDays = null;
            $forecastEtaDate = null;
            $forecastNote = null;
            if ($purchase !== null && $purchase > 0) {
                if ($isPaidBack) {
                    $forecastNote = 'paid';
                } elseif ($recentDailyNet > 0) {
                    $remaining = $purchase - $net;
                    $forecastEtaDays = (int) ceil($remaining / $recentDailyNet);
                    $forecastEtaDate = $now->copy()->addDays($forecastEtaDays);
                    $forecastNote = 'projected';
                } else {
                    $forecastNote = 'stalled';
                }
            }

            $activeDays30 = (int) ($activeDaysPerCar[$car->id] ?? 0);
            $util30 = (int) round($activeDays30 / 30 * 100);

            return [
                'car' => $car,
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'purchase' => $purchase,
                'roi_pct' => $roiPct,
                'payback_pct' => $paybackPct,
                'is_paid_back' => $isPaidBack,
                'days_in_fleet' => $daysInFleet,
                'daily_avg_net' => $dailyAvg,
                'daily_avg_income' => $dailyAvgIncome,
                'recent_daily_net' => $recentDailyNet,
                'forecast_eta_days' => $forecastEtaDays,
                'forecast_eta_date' => $forecastEtaDate,
                'forecast_note' => $forecastNote,
                'active_days_30' => $activeDays30,
                'util_30_pct' => $util30,
                'in_use_now' => in_array($car->id, $openRentalCarIds, true),
            ];
        })->sortByDesc('net')->values();

        $kpi = [
            'investment' => $perCar->sum('purchase') ?? 0,
            'income' => $perCar->sum('income'),
            'expense' => $perCar->sum('expense'),
            'net' => $perCar->sum('net'),
        ];
        $kpi['fleet_roi_pct'] = $kpi['investment'] > 0 ? round($kpi['net'] / $kpi['investment'] * 100, 1) : null;
        $kpi['paid_back_cars'] = $perCar->filter(fn ($r) => $r['is_paid_back'] === true)->count();
        $kpi['cars_with_purchase'] = $perCar->filter(fn ($r) => $r['purchase'] !== null && $r['purchase'] > 0)->count();
        $kpi['active_drivers'] = User::whereHas('rentals', fn ($q) => $q->where('status', 'open'))->count();
        $kpi['open_rentals'] = Rental::where('status', 'open')->count();
        $kpi['avg_user_balance'] = (int) round((float) User::where('role', 'driver')->avg('balance') ?? 0);

        $avgRentalDays = Rental::whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, closed_at)) / 86400 as days')
            ->value('days');
        $kpi['avg_rental_days'] = $avgRentalDays !== null ? round((float) $avgRentalDays, 1) : null;

        $cashflow = $this->buildDailyCashflow($windowStart, $now);
        $cohorts = $this->buildCohorts($now);
        $repairs = $this->buildRepairStats($cars);
        $seasonality = $this->buildSeasonality($now);
        $availableMonths = $this->availableReportMonths();

        return view('stats.unit-economics', compact(
            'perCar', 'kpi', 'cashflow', 'now',
            'cohorts', 'repairs', 'seasonality', 'availableMonths',
        ));
    }

    // ----- helpers -------------------------------------------------------

    private function buildDailyCashflow(Carbon $from, Carbon $to): array
    {
        $dailyIncome = CarTransaction::query()->where('type', 'income')->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')->groupBy('day')->pluck('total', 'day');
        $dailyExpense = CarTransaction::query()->where('type', 'expense')->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')->groupBy('day')->pluck('total', 'day');

        $out = [];
        $cum = 0.0;
        $cur = $from->copy();
        while ($cur->lte($to)) {
            $k = $cur->toDateString();
            $inc = (float) ($dailyIncome[$k] ?? 0);
            $exp = (float) ($dailyExpense[$k] ?? 0);
            $cum += ($inc - $exp);
            $out[] = [
                'date' => $k,
                'label' => $cur->format('d.m'),
                'income' => round($inc, 2),
                'expense' => round($exp, 2),
                'net' => round($inc - $exp, 2),
                'cumulative' => round($cum, 2),
            ];
            $cur->addDay();
        }
        return $out;
    }

    /**
     * Driver cohorts (signup month) + retention heatmap.
     */
    private function buildCohorts(Carbon $now): array
    {
        $rows = User::query()->where('role', 'driver')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as cohort, COUNT(*) as size')
            ->groupBy('cohort')->orderBy('cohort', 'desc')->limit(12)->get();

        if ($rows->isEmpty()) {
            return ['cohorts' => [], 'retention' => [], 'maxMonths' => 0];
        }

        $cohortsList = [];
        $retention = [];
        $maxMonths = 0;

        foreach ($rows as $row) {
            $cohortMonth = Carbon::createFromFormat('Y-m-d', $row->cohort.'-01')->startOfMonth();
            $userIds = User::where('role', 'driver')
                ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$row->cohort])
                ->pluck('id')->all();
            if (empty($userIds)) continue;

            $spend = (float) Transaction::whereIn('user_id', $userIds)->where('type', 'withdrawal')->sum('amount');
            $topup = (float) Transaction::whereIn('user_id', $userIds)->where('type', 'deposit')->sum('amount');

            $cohortsList[] = [
                'cohort' => $row->cohort,
                'label' => \Illuminate\Support\Str::ucfirst($cohortMonth->locale('ru')->isoFormat('MMMM YYYY')),
                'size' => (int) $row->size,
                'spend' => round($spend, 2),
                'topup' => round($topup, 2),
                'arpu' => round($spend / max(1, $row->size), 2),
            ];

            $monthsSince = (int) $cohortMonth->diffInMonths($now->copy()->startOfMonth());
            $maxMonths = max($maxMonths, $monthsSince);
            $cells = [];
            for ($m = 0; $m <= $monthsSince; $m++) {
                $pStart = $cohortMonth->copy()->addMonths($m)->startOfMonth();
                $pEnd = $pStart->copy()->endOfMonth();
                $active = (int) Transaction::whereIn('user_id', $userIds)
                    ->whereBetween('created_at', [$pStart, $pEnd])
                    ->distinct('user_id')->count('user_id');
                $cells[] = [
                    'm' => $m,
                    'count' => $active,
                    'pct' => $row->size > 0 ? (int) round($active / $row->size * 100) : 0,
                ];
            }
            $retention[] = [
                'cohort' => $row->cohort,
                'label' => \Illuminate\Support\Str::ucfirst($cohortMonth->locale('ru')->isoFormat('MMMM YYYY')),
                'size' => (int) $row->size,
                'cells' => $cells,
            ];
        }

        return ['cohorts' => $cohortsList, 'retention' => $retention, 'maxMonths' => $maxMonths];
    }

    private function buildRepairStats($cars): array
    {
        if ($cars->isEmpty()) return [];

        $stats = CarTransaction::query()->where('type', 'expense')
            ->selectRaw('car_id, COUNT(*) as cnt, SUM(amount) as total, AVG(amount) as avg_amount, MAX(created_at) as last_at, MIN(created_at) as first_at')
            ->groupBy('car_id')->get()->keyBy('car_id');

        $out = [];
        foreach ($cars as $car) {
            $s = $stats->get($car->id);
            if (! $s || $s->cnt == 0) continue;
            $first = Carbon::parse($s->first_at);
            $last = Carbon::parse($s->last_at);
            $mtbr = $s->cnt > 1 ? round($first->diffInDays($last) / ($s->cnt - 1), 1) : null;
            $out[] = [
                'car' => $car,
                'count' => (int) $s->cnt,
                'total' => round((float) $s->total, 2),
                'avg' => round((float) $s->avg_amount, 2),
                'last_at' => $last,
                'first_at' => $first,
                'mtbr_days' => $mtbr,
            ];
        }
        usort($out, fn ($a, $b) => $b['total'] <=> $a['total']);
        return $out;
    }

    private function buildSeasonality(Carbon $now): array
    {
        $start = $now->copy()->subDays(89)->startOfDay();
        $rows = CarTransaction::query()->where('type', 'income')->where('created_at', '>=', $start)
            ->selectRaw('DAYOFWEEK(created_at) as dow, HOUR(created_at) as hr, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('dow', 'hr')->get();

        $mysqlToMon = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];
        $dayLabels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

        $matrix = [];
        for ($d = 0; $d < 7; $d++) {
            $matrix[$d] = array_fill(0, 24, ['total' => 0.0, 'cnt' => 0]);
        }
        $max = 0.0;
        foreach ($rows as $r) {
            $d = $mysqlToMon[$r->dow] ?? null;
            if ($d === null) continue;
            $matrix[$d][(int) $r->hr] = [
                'total' => (float) $r->total,
                'cnt' => (int) $r->cnt,
            ];
            if ((float) $r->total > $max) $max = (float) $r->total;
        }

        return [
            'matrix' => $matrix,
            'days' => $dayLabels,
            'max' => $max,
            'window_days' => 90,
            'from' => $start->toDateString(),
            'to' => $now->toDateString(),
        ];
    }

    private function availableReportMonths(): array
    {
        $months = collect();
        $months = $months->merge(
            CarTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as m')->distinct()->pluck('m')
        );
        $months = $months->merge(
            Transaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as m')->distinct()->pluck('m')
        );
        $months = $months->filter()->unique()->values()->sortDesc();
        if ($months->isEmpty()) {
            $months = collect([now()->format('Y-m')]);
        }
        return $months->values()->all();
    }
}
