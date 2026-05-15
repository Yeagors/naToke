<?php

namespace App\Http\Controllers;

use App\Enums\CarTransactionType;
use App\Http\Requests\StoreCarTransactionRequest;
use App\Models\Car;
use App\Models\CarTransaction;
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

        return back()->with('status', "{$label} на сумму ".number_format($amount, 2, '.', ' ').' ₽ зафиксирован.');
    }
}
