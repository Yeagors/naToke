<?php

namespace App\Console\Commands;

use App\Enums\CarTransactionType;
use App\Enums\RentalStatus;
use App\Enums\TransactionType;
use App\Models\CarTransaction;
use App\Models\Rental;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessRentalCharges extends Command
{
    protected $signature = 'rentals:charge
        {--dry : Show what would be charged without writing}';

    protected $description = 'Charge active rentals whose next_charge_at has passed.';

    public function handle(): int
    {
        $now = now();
        $dry = (bool) $this->option('dry');

        $rentals = Rental::with(['user', 'car', 'tariff'])
            ->where('status', RentalStatus::Open->value)
            ->whereNotNull('next_charge_at')
            ->where('next_charge_at', '<=', $now)
            ->get();

        if ($rentals->isEmpty()) {
            $this->info('Nothing to charge.');
            return self::SUCCESS;
        }

        $this->info('Found '.$rentals->count().' rental(s) ready to be charged.');

        $charged = 0;
        $failed = 0;

        foreach ($rentals as $rental) {
            try {
                if ($dry) {
                    $this->line("  [DRY] Rental #{$rental->id}: would charge {$rental->amount} ₽ from user #{$rental->user_id}, credit car #{$rental->car_id}.");
                    continue;
                }

                DB::transaction(fn () => $this->charge($rental));
                $charged++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("Rental #{$rental->id} failed: ".$e->getMessage());
                report($e);
            }
        }

        $this->info("Done. Charged: {$charged}. Failed: {$failed}.");
        return self::SUCCESS;
    }

    /**
     * Settle one rental period: withdraw from user, credit car, advance next_charge_at.
     * May loop if a rental was skipped for several periods (keeps the schedule consistent).
     */
    private function charge(Rental $rental): void
    {
        $rental->refresh();
        if ($rental->status !== RentalStatus::Open) {
            return;
        }
        if ($rental->next_charge_at === null || $rental->next_charge_at->isFuture()) {
            return;
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

        // 4. Advance schedule
        $rental->last_charged_at = now();
        $rental->next_charge_at = $rental->computeNextChargeFrom($rental->next_charge_at);
        $rental->save();

        $this->line("  ✓ Rental #{$rental->id}: -{$amount} ₽ user → +{$amount} ₽ car (next: {$rental->next_charge_at->format('d.m.Y H:i')})");
    }
}
