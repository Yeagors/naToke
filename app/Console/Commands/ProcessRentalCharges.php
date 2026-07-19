<?php

namespace App\Console\Commands;

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Services\RentalCharger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessRentalCharges extends Command
{
    protected $signature = 'rentals:charge
        {--dry : Show what would be charged without writing}';

    protected $description = 'Charge active rentals whose next_charge_at has passed.';

    public function __construct(private RentalCharger $charger)
    {
        parent::__construct();
    }

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

                $result = DB::transaction(fn () => $this->charger->charge($rental));
                if ($result['charged'] ?? false) {
                    $charged++;
                    $this->line("  ✓ Rental #{$rental->id}: -{$result['amount']} ₽"
                        .(($result['buyout_completed'] ?? false)
                            ? ' [ВЫКУП ЗАВЕРШЁН]'
                            : ' (next: '.optional($result['next_charge_at'] ?? null)->format('d.m.Y H:i').')'));
                }
            } catch (Throwable $e) {
                $failed++;
                $this->error("Rental #{$rental->id} failed: ".$e->getMessage());
                report($e);
            }
        }

        $this->info("Done. Charged: {$charged}. Failed: {$failed}.");
        return self::SUCCESS;
    }

}
