<?php

namespace App\Http\Controllers;

use App\Enums\RentalStatus;
use App\Enums\TransactionType;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Car;
use App\Models\Rental;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $counts = [
            'all'    => Rental::count(),
            'open'   => Rental::where('status', RentalStatus::Open->value)->count(),
            'paused' => Rental::where('status', RentalStatus::Paused->value)->count(),
            'closed' => Rental::where('status', RentalStatus::Closed->value)->count(),
        ];

        $query = Rental::query()
            ->with(['car', 'user', 'tariff'])
            ->latest('id');

        if (in_array($status, ['open', 'paused', 'closed'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('user', function ($uq) use ($q) {
                    $uq->where('last_name', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('middle_name', 'like', "%{$q}%")
                        ->orWhere('login', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                })->orWhereHas('car', function ($cq) use ($q) {
                    $cq->where('license_plate', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%");
                });
            });
        }

        $rentals = $query->paginate(20)->withQueryString();

        return view('rentals.index', compact('rentals', 'status', 'counts', 'q'));
    }

    public function show(Rental $rental): View
    {
        $rental->load([
            'car',
            'user',
            'tariff',
            'creator',
            'userTransactions' => fn ($q) => $q->take(50),
            'carTransactions' => fn ($q) => $q->take(50),
        ]);

        return view('rentals.show', compact('rental'));
    }

    public function store(StoreRentalRequest $request, Car $car): RedirectResponse
    {
        $data = $request->validated();
        $tariff = Tariff::findOrFail($data['tariff_id']);
        $user = User::findOrFail($data['user_id']);

        // Don't allow a second open rental on the same car.
        if ($car->rentals()->whereIn('status', [RentalStatus::Open->value, RentalStatus::Paused->value])->exists()) {
            return back()->withErrors(['user_id' => 'У авто уже есть активная или приостановленная аренда — закройте её перед началом новой.']);
        }

        $startedAt = ! empty($data['started_at']) ? Carbon::parse($data['started_at']) : now();

        $rental = DB::transaction(function () use ($car, $user, $tariff, $startedAt, $data, $request) {
            $rental = $car->rentals()->create([
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'status' => RentalStatus::Open,
                'amount' => $tariff->amount,
                'period' => $tariff->period,
                'period_count' => $tariff->period_count,
                'deposit_amount' => $tariff->deposit_amount,
                'extras' => $tariff->extras,
                'started_at' => $startedAt,
                'next_charge_at' => $tariff->period->addTo($startedAt, $tariff->period_count),
                'comment' => $data['comment'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Deposit charge (withdraw from user balance) — only if tariff has deposit > 0
            $deposit = (float) $tariff->deposit_amount;
            if ($deposit > 0) {
                $user->refresh();
                $user->balance = (float) $user->balance - $deposit;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'rental_id' => $rental->id,
                    'type' => TransactionType::Withdrawal,
                    'amount' => $deposit,
                    'balance_after' => $user->balance,
                    'comment' => "Депозит по аренде #{$rental->id}",
                    'created_by' => $request->user()->id,
                ]);
            }

            return $rental;
        });

        ActivityLogger::log(
            'rentals.created',
            $rental,
            "Оформлена аренда #{$rental->id}: {$rental->user->full_name} → {$rental->car->display_name} · тариф {$tariff->name}"
        );

        return redirect()->route('rentals.show', $rental)->with('status', 'Аренда оформлена.');
    }

    public function pause(Rental $rental): RedirectResponse
    {
        if (! $rental->isOpen()) {
            return back()->with('status', 'Только активную аренду можно приостановить.');
        }

        $rental->status = RentalStatus::Paused;
        $rental->paused_at = now();
        $rental->save();

        ActivityLogger::log('rentals.paused', $rental, "Приостановлена аренда #{$rental->id} ({$rental->car->display_name})");

        return back()->with('status', 'Аренда приостановлена.');
    }

    public function resume(Rental $rental): RedirectResponse
    {
        if (! $rental->isPaused()) {
            return back()->with('status', 'Только приостановленную аренду можно возобновить.');
        }

        $rental->status = RentalStatus::Open;
        $rental->paused_at = null;
        $rental->save();

        ActivityLogger::log('rentals.resumed', $rental, "Возобновлена аренда #{$rental->id} ({$rental->car->display_name})");

        return back()->with('status', 'Аренда возобновлена.');
    }

    public function close(Rental $rental): RedirectResponse
    {
        if ($rental->isClosed()) {
            return back()->with('status', 'Аренда уже закрыта.');
        }

        $rental->status = RentalStatus::Closed;
        $rental->closed_at = now();
        $rental->next_charge_at = null;
        $rental->save();

        ActivityLogger::log('rentals.closed', $rental, "Закрыта аренда #{$rental->id} ({$rental->car->display_name})");

        return back()->with('status', 'Аренда закрыта.');
    }
}
