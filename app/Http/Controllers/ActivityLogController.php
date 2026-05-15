<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private const GROUPS = ['auth', 'users', 'tariffs', 'cars', 'rentals', 'money'];

    public function index(Request $request): View
    {
        $group = (string) $request->query('group', 'all');
        $q = trim((string) $request->query('q', ''));
        $actorId = $request->query('actor_id');
        $from = $request->query('from');
        $to = $request->query('to');

        $counts = [
            'all' => ActivityLog::count(),
            'auth' => ActivityLog::where('action', 'like', 'auth.%')->count(),
            'users' => ActivityLog::where(function ($w) {
                $w->where('action', 'like', 'users.%')->orWhere('action', 'like', 'profile.%');
            })->count(),
            'tariffs' => ActivityLog::where('action', 'like', 'tariffs.%')->count(),
            'cars' => ActivityLog::where('action', 'like', 'cars.%')->count(),
            'rentals' => ActivityLog::where(function ($w) {
                $w->where('action', 'like', 'rentals.%')->orWhere('action', 'like', 'cron.rental%');
            })->count(),
            'money' => ActivityLog::where(function ($w) {
                $w->where('action', 'like', 'transactions.%')->orWhere('action', 'like', 'car_transactions.%');
            })->count(),
        ];

        $query = ActivityLog::query()
            ->with('actor')
            ->latest('id');

        if (in_array($group, self::GROUPS, true)) {
            $query->where(function ($w) use ($group) {
                match ($group) {
                    'auth' => $w->where('action', 'like', 'auth.%'),
                    'users' => $w->where('action', 'like', 'users.%')->orWhere('action', 'like', 'profile.%'),
                    'tariffs' => $w->where('action', 'like', 'tariffs.%'),
                    'cars' => $w->where('action', 'like', 'cars.%'),
                    'rentals' => $w->where('action', 'like', 'rentals.%')->orWhere('action', 'like', 'cron.rental%'),
                    'money' => $w->where('action', 'like', 'transactions.%')->orWhere('action', 'like', 'car_transactions.%'),
                };
            });
        }

        if ($actorId) {
            $query->where('user_id', (int) $actorId);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")
                    ->orWhere('subject_label', 'like', "%{$q}%")
                    ->orWhere('actor_label', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%");
            });
        }

        if ($from) {
            try { $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()); }
            catch (\Throwable $e) { /* ignore bad input */ }
        }
        if ($to) {
            try { $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()); }
            catch (\Throwable $e) { /* ignore bad input */ }
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('logs.index', compact('logs', 'group', 'counts', 'q', 'actorId', 'from', 'to'));
    }
}
