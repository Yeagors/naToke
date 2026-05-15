<?php

namespace App\Http\Controllers;

use App\Enums\CarTransactionType;
use App\Http\Requests\StoreCarTransactionRequest;
use App\Models\Car;
use App\Models\CarTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CarTransactionController extends Controller
{
    public function store(StoreCarTransactionRequest $request, Car $car): RedirectResponse
    {
        $data = $request->validated();
        $type = CarTransactionType::from($data['type']);
        $amount = (float) $data['amount'];

        DB::transaction(function () use ($car, $type, $amount, $data, $request) {
            $car->refresh();
            $newBalance = (float) $car->balance + $type->sign() * $amount;
            $car->balance = $newBalance;
            $car->save();

            CarTransaction::create([
                'car_id' => $car->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'comment' => $data['comment'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        $label = $type === CarTransactionType::Income ? 'Приход' : 'Расход';
        $action = $type === CarTransactionType::Income ? 'car_transactions.income' : 'car_transactions.expense';
        $formatted = number_format($amount, 2, '.', ' ');
        $commentSuffix = ! empty($data['comment']) ? " · {$data['comment']}" : '';
        ActivityLogger::log(
            $action,
            $car,
            "{$label} {$formatted} ₽ по авто {$car->display_name}{$commentSuffix}",
        );

        return back()->with('status', "{$label} на сумму ".$formatted.' ₽ зафиксирован.');
    }
}
