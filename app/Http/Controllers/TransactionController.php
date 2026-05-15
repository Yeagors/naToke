<?php

namespace App\Http\Controllers;

use App\Enums\CarTransactionType;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\CarTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Unified transaction list across User and Car balance ledgers.
     *
     * Filters:
     *   direction = all|user|car
     *   sign      = all|in|out
     *   from / to = YYYY-MM-DD (inclusive)
     *   q         = free-text in comment / subject name
     */
    public function index(Request $request): View
    {
        $direction = (string) $request->query('direction', 'all');
        $sign = (string) $request->query('sign', 'all');
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('q', ''));

        $userQ = Transaction::query()->with(['user', 'creator', 'rental.car']);
        $carQ = CarTransaction::query()->with(['car', 'creator', 'rental.user']);

        // sign
        if ($sign === 'in') {
            $userQ->where('type', TransactionType::Deposit->value);
            $carQ->where('type', CarTransactionType::Income->value);
        } elseif ($sign === 'out') {
            $userQ->where('type', TransactionType::Withdrawal->value);
            $carQ->where('type', CarTransactionType::Expense->value);
        }

        // date range
        if ($from) {
            try {
                $fromDt = Carbon::parse($from)->startOfDay();
                $userQ->where('created_at', '>=', $fromDt);
                $carQ->where('created_at', '>=', $fromDt);
            } catch (\Throwable $e) {}
        }
        if ($to) {
            try {
                $toDt = Carbon::parse($to)->endOfDay();
                $userQ->where('created_at', '<=', $toDt);
                $carQ->where('created_at', '<=', $toDt);
            } catch (\Throwable $e) {}
        }

        // free-text search on comment and related labels
        if ($q !== '') {
            $userQ->where(function ($w) use ($q) {
                $w->where('comment', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('last_name', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%")
                            ->orWhere('login', 'like', "%{$q}%");
                    });
            });
            $carQ->where(function ($w) use ($q) {
                $w->where('comment', 'like', "%{$q}%")
                    ->orWhereHas('car', function ($cq) use ($q) {
                        $cq->where('brand', 'like', "%{$q}%")
                            ->orWhere('model', 'like', "%{$q}%")
                            ->orWhere('license_plate', 'like', "%{$q}%");
                    });
            });
        }

        $counts = [
            'all' => (clone $userQ)->count() + (clone $carQ)->count(),
            'user' => (clone $userQ)->count(),
            'car' => (clone $carQ)->count(),
        ];

        // Normalize into a unified view-model
        $userItems = $direction === 'car' ? collect() : $userQ->get()->map(fn (Transaction $t) => $this->normalizeUser($t));
        $carItems = $direction === 'user' ? collect() : $carQ->get()->map(fn (CarTransaction $t) => $this->normalizeCar($t));

        $merged = $userItems->concat($carItems)
            ->sortByDesc(fn ($row) => $row['sort_key'])
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $items = new LengthAwarePaginator(
            $merged->slice(($page - 1) * $perPage, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totals = [
            'in' => $merged->where('sign', '+')->sum('amount'),
            'out' => $merged->where('sign', '-')->sum('amount'),
        ];

        return view('transactions.index', compact('items', 'direction', 'sign', 'from', 'to', 'q', 'counts', 'totals'));
    }

    private function normalizeUser(Transaction $t): array
    {
        $isDeposit = $t->type === TransactionType::Deposit;
        return [
            'sort_key' => $t->created_at->getTimestamp() * 1000 + ($t->id % 1000),
            'id' => $t->id,
            'kind' => 'user',
            'kind_label' => 'Пользователь',
            'created_at' => $t->created_at,
            'amount' => (float) $t->amount,
            'sign' => $isDeposit ? '+' : '-',
            'type_label' => $t->type->label(),
            'type_class' => $isDeposit ? 'badge-deposit' : 'badge-withdrawal',
            'comment' => $t->comment,
            'subject_name' => $t->user?->full_name,
            'subject_url' => $t->user_id ? route('users.show', $t->user_id) : null,
            'rental_id' => $t->rental_id,
            'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
            'created_by' => $t->creator?->short_name,
        ];
    }

    private function normalizeCar(CarTransaction $t): array
    {
        $isIncome = $t->type === CarTransactionType::Income;
        return [
            'sort_key' => $t->created_at->getTimestamp() * 1000 + ($t->id % 1000),
            'id' => $t->id,
            'kind' => 'car',
            'kind_label' => 'Авто',
            'created_at' => $t->created_at,
            'amount' => (float) $t->amount,
            'sign' => $isIncome ? '+' : '-',
            'type_label' => $t->type->label(),
            'type_class' => $isIncome ? 'badge-deposit' : 'badge-withdrawal',
            'comment' => $t->comment,
            'subject_name' => $t->car?->display_name,
            'subject_url' => $t->car_id ? route('cars.show', $t->car_id) : null,
            'rental_id' => $t->rental_id,
            'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
            'created_by' => $t->creator?->short_name,
        ];
    }

    public function store(StoreTransactionRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $type = TransactionType::from($data['type']);
        $amount = (float) $data['amount'];

        DB::transaction(function () use ($user, $type, $amount, $data, $request) {
            $user->refresh();
            $newBalance = (float) $user->balance + $type->sign() * $amount;
            $user->balance = $newBalance;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'comment' => $data['comment'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        $label = $type === TransactionType::Deposit ? 'Пополнение' : 'Списание';
        $action = $type === TransactionType::Deposit ? 'transactions.deposit' : 'transactions.withdrawal';
        $commentSuffix = ! empty($data['comment']) ? " · {$data['comment']}" : '';
        $formatted = number_format($amount, 2, '.', ' ');
        ActivityLogger::log(
            $action,
            $user,
            "{$label} {$formatted} ₽ для {$user->full_name}{$commentSuffix}",
        );

        return back()->with('status', "{$label} на сумму ".$formatted.' ₽ проведено.');
    }
}
