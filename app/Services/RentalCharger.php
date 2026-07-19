<?php

namespace App\Services;

use App\Enums\CarTransactionType;
use App\Enums\RentalStatus;
use App\Enums\TransactionType;
use App\Models\CarTransaction;
use App\Models\Rental;
use App\Models\Transaction;

/**
 * Settles one rental period: withdraw from the user, credit the car, advance
 * buyout progress and next_charge_at. Used by the cron command and by rental
 * creation (immediate first charge).
 */
class RentalCharger
{
    /**
     * @return array{charged:bool, buyout_completed?:bool, amount?:float, next_charge_at?:\Illuminate\Support\Carbon|null}
     */
    public function charge(Rental $rental): array
    {
        $rental->refresh();
        if ($rental->status !== RentalStatus::Open) {
            return ['charged' => false];
        }
        if ($rental->next_charge_at === null || $rental->next_charge_at->isFuture()) {
            return ['charged' => false];
        }

        $amount = (float) $rental->amount;
        $user = $rental->user;
        $car = $rental->car;

        // 1. User withdrawal
        $user->balance = (float) $user->balance - $amount;
        $user->save();
        Transaction::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'type' => TransactionType::Withdrawal,
            'amount' => $amount,
            'balance_after' => $user->balance,
            'comment' => "Аренда #{$rental->id} · списание за период",
            'created_by' => null,
        ]);

        // 2. Extras (additional fixed withdrawals attached to this period)
        foreach ((array) $rental->extras as $extra) {
            $label = trim((string) ($extra['label'] ?? ''));
            $extraAmount = (float) ($extra['amount'] ?? 0);
            if ($extraAmount <= 0 || $label === '') {
                continue;
            }
            $user->balance = (float) $user->balance - $extraAmount;
            $user->save();
            Transaction::create([
                'user_id' => $user->id,
                'rental_id' => $rental->id,
                'type' => TransactionType::Withdrawal,
                'amount' => $extraAmount,
                'balance_after' => $user->balance,
                'comment' => "Аренда #{$rental->id} · доп: {$label}",
                'created_by' => null,
            ]);
        }

        // 3. Car income
        $car->balance = (float) $car->balance + $amount;
        $car->save();
        CarTransaction::create([
            'car_id' => $car->id,
            'rental_id' => $rental->id,
            'type' => CarTransactionType::Income,
            'amount' => $amount,
            'balance_after' => $car->balance,
            'comment' => "Аренда #{$rental->id} · {$user->short_name}",
            'created_by' => null,
        ]);

        // 4. Buyout (lease-to-own) progress
        $buyoutMessage = '';
        $buyoutCompleted = false;
        if ($rental->is_buyout && $rental->buyout_remaining !== null) {
            $newRemaining = max(0, (float) $rental->buyout_remaining - $amount);
            $newDaysRem = max(0, (int) ($rental->buyout_days_remaining ?? 0) - 1);
            $rental->buyout_remaining = $newRemaining;
            $rental->buyout_days_remaining = $newDaysRem;

            if ($newRemaining <= 0) {
                $rental->buyout_completed_at = now();
                $rental->status = RentalStatus::Closed;
                $rental->closed_at = now();
                $rental->next_charge_at = null;
                $buyoutCompleted = true;
                $buyoutMessage = ' · ВЫКУП ЗАВЕРШЁН';
            } else {
                $buyoutMessage = sprintf(
                    ' · выкуп: осталось %s ₽ / %d дн.',
                    number_format($newRemaining, 2, '.', ' '),
                    $newDaysRem,
                );
            }
        }

        // 5. Advance schedule (only if not just auto-closed by buyout)
        $rental->last_charged_at = now();
        if (! $buyoutCompleted) {
            $rental->next_charge_at = $rental->computeNextChargeFrom($rental->next_charge_at);
        }
        $rental->save();

        // 6. Audit log (actor = null → "system")
        ActivityLogger::log(
            'cron.rental_charge',
            $rental,
            sprintf(
                'Списание по аренде #%d · %s ₽ с %s, +%s ₽ на %s%s',
                $rental->id,
                number_format($amount, 2, '.', ' '),
                $user->short_name,
                number_format($amount, 2, '.', ' '),
                $car->display_name,
                $buyoutMessage,
            ),
        );

        if ($buyoutCompleted) {
            ActivityLogger::log(
                'cron.rental_buyout_completed',
                $rental,
                "Авто {$car->display_name} полностью выкуплено арендатором {$user->full_name} (аренда #{$rental->id})"
            );
        }

        return [
            'charged' => true,
            'buyout_completed' => $buyoutCompleted,
            'amount' => $amount,
            'next_charge_at' => $rental->next_charge_at,
        ];
    }
}
