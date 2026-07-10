<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class ExpireStalePayments extends Command
{
    protected $signature = 'payments:expire-stale {--hours=24}';

    protected $description = 'Cancel top-up requests stuck in "pending" longer than N hours.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours') ?: 24;
        $cutoff = now()->subHours($hours);

        $stale = PaymentRequest::where('status', PaymentStatus::Pending->value)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $p) {
            $p->status = PaymentStatus::Cancelled;
            $p->failed_reason = "не оплачен за {$hours} ч";
            $p->save();
            ActivityLogger::log('payments.expired', $p, "Платёж #{$p->id} автоматически отменён: не оплачен за {$hours} ч");
        }

        $this->info("Cancelled {$stale->count()} stale payment(s) (older than {$hours}h).");

        return self::SUCCESS;
    }
}
