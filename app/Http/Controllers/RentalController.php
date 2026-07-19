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
use App\Services\RentalCharger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    public function show(Request $request, Rental $rental): View
    {
        $user = $request->user();

        // Drivers can only see their own rental; admins see everything.
        if (! $user->isAdmin() && $rental->user_id !== $user->id) {
            abort(403, 'Эта аренда оформлена не на вас.');
        }

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

    /**
     * Generate the rental contract (PDF) filled from the renter + car + landlord profiles.
     * Buyout rentals (is_buyout) use the lease-to-own template.
     */
    public function contract(Rental $rental): Response
    {
        $rental->load(['car', 'user', 'creator', 'tariff', 'batteries']);

        // Landlord (Арендодатель): configured owner by login, else whoever opened the rental.
        $landlord = null;
        if ($login = config('contract.landlord_login')) {
            $landlord = User::where('login', $login)->first();
        }
        $landlord = $landlord ?: $rental->creator ?: new User();
        $renter = $rental->user ?: new User();
        $car = $rental->car;

        // Battery list: from the attached АКБ (may be several), else the car's own fields.
        $batteries = $rental->batteries->map(fn ($b) => ['capacity' => $b->capacity, 'vin' => $b->vin])->all();
        if ($batteries === [] && $car && (filled($car->battery_capacity) || filled($car->battery_number))) {
            $batteries = [['capacity' => $car->battery_capacity, 'vin' => $car->battery_number]];
        }

        // Block download until every field the contract needs is filled in.
        $missing = $this->contractMissingFields($renter, $landlord, $car, $batteries);
        if ($missing !== []) {
            return redirect()->route('rentals.show', $rental)->with('toast', [
                'type' => 'error',
                'message' => 'Договор не готов — заполните поля: '.implode('; ', array_unique($missing)).'.',
            ]);
        }

        $view = $rental->is_buyout ? 'contracts.buyout' : 'contracts.rental';
        $prefix = $rental->is_buyout ? 'dogovor-vykupa-' : 'dogovor-arendy-';

        $pdf = Pdf::loadView($view, compact('rental', 'renter', 'car', 'landlord', 'batteries'))->setPaper('a4');

        return $pdf->download($prefix.$rental->id.'.pdf');
    }

    /**
     * Returns a list of human-readable field names required for the contract that
     * are currently empty (across renter, landlord and car).
     */
    private function contractMissingFields(User $renter, User $landlord, ?Car $car, array $batteries = []): array
    {
        $userFields = [
            'last_name' => 'фамилия',
            'first_name' => 'имя',
            'birth_date' => 'дата рождения',
            'birth_place' => 'место рождения',
            'passport_series' => 'серия паспорта',
            'passport_number' => 'номер паспорта',
            'passport_issued_by' => 'кем выдан паспорт',
            'passport_issued_at' => 'дата выдачи паспорта',
            'address_registration' => 'адрес регистрации',
            'phone' => 'телефон',
        ];
        $carFields = [
            'brand' => 'марка',
            'model' => 'модель',
            'frame_number' => 'номер рамы',
        ];

        $missing = [];
        foreach ($userFields as $f => $label) {
            if (blank($renter->{$f})) $missing[] = "арендатор — {$label}";
        }
        foreach ($userFields as $f => $label) {
            if (blank($landlord->{$f})) $missing[] = "арендодатель — {$label}";
        }
        foreach ($carFields as $f => $label) {
            if (! $car || blank($car->{$f})) $missing[] = "авто — {$label}";
        }
        // Battery (from the attached АКБ or the car's own fields).
        if ($batteries === []) {
            $missing[] = 'АКБ — не привязана (ни к аренде, ни к авто)';
        } else {
            foreach ($batteries as $b) {
                if (blank($b['capacity'] ?? null)) $missing[] = 'АКБ — ёмкость';
                if (blank($b['vin'] ?? null)) $missing[] = 'АКБ — номер (вин)';
            }
        }

        return $missing;
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

        // Every chosen battery must be free (not on another active rental).
        $batteryIds = array_values(array_unique(array_map('intval', (array) ($data['battery_ids'] ?? []))));
        foreach ($batteryIds as $bid) {
            $battery = \App\Models\Battery::find($bid);
            if (! $battery || ! $battery->isAvailable()) {
                return back()->withErrors(['battery_ids' => 'Одна из выбранных АКБ уже используется в другой аренде.'])->withInput();
            }
        }

        $startedAt = ! empty($data['started_at']) ? Carbon::parse($data['started_at']) : now();

        $rental = DB::transaction(function () use ($car, $user, $tariff, $startedAt, $data, $request, $batteryIds) {
            $rental = $car->rentals()->create([
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'status' => RentalStatus::Open,
                'amount' => $tariff->amount,
                'period' => $tariff->period,
                'period_count' => $tariff->period_count,
                'deposit_amount' => $tariff->deposit_amount,
                'extras' => $tariff->extras,
                // Buyout (lease-to-own) snapshot — copied from tariff at start so later
                // changes to the tariff don't affect this rental.
                'is_buyout' => (bool) $tariff->is_buyout,
                'buyout_price' => $tariff->is_buyout ? $tariff->buyout_price : null,
                'buyout_days_total' => $tariff->is_buyout ? $tariff->buyout_days : null,
                'buyout_remaining' => $tariff->is_buyout ? $tariff->buyout_price : null,
                'buyout_days_remaining' => $tariff->is_buyout ? $tariff->buyout_days : null,
                'started_at' => $startedAt,
                // Первый платёж списывается сразу при открытии (ниже), поэтому next = старт.
                'next_charge_at' => $startedAt,
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

            // Attach the chosen batteries (many-to-many).
            if ($batteryIds !== []) {
                $rental->batteries()->attach($batteryIds);
            }

            return $rental;
        });

        ActivityLogger::log(
            'rentals.created',
            $rental,
            "Оформлена аренда #{$rental->id}: {$rental->user->full_name} → {$rental->car->display_name} · тариф {$tariff->name}"
        );

        // Первый платёж списывается сразу при открытии аренды. Не блокируем создание,
        // если что-то пошло не так — крон спишет следующим проходом (next_charge_at = старт).
        try {
            DB::transaction(fn () => app(RentalCharger::class)->charge($rental));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('rentals.show', $rental)->with('status', 'Аренда оформлена, первый платёж списан.');
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
