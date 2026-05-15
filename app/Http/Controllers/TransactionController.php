<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
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

        return back()->with('status', "{$label} на сумму ".number_format($amount, 2, '.', ' ').' ₽ проведено.');
    }
}
