<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Console\Command;
use Throwable;

class RefundPayment extends Command
{
    protected $signature = 'payments:refund {payment : PaymentRequest local id}';

    protected $description = 'Cancel/refund a payment on its gateway and reverse the user balance.';

    public function handle(PaymentGatewayManager $gateways): int
    {
        $pr = PaymentRequest::find($this->argument('payment'));
        if (! $pr) {
            $this->error('Payment not found.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Payment #%d · user %d · amount %s (charge %s) · status %s · gateway %s · PaymentId %s',
            $pr->id, $pr->user_id, $pr->amount, $pr->charge_amount, $pr->status->value, $pr->gateway, $pr->external_id
        ));

        try {
            $resp = $gateways->make($pr->gateway)->cancel($pr);
        } catch (Throwable $e) {
            $this->error('Cancel failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $pr->refresh();
        $this->info('Done. Provider status: '.($resp['Status'] ?? '?').'. Local status: '.$pr->status->value.'.');
        return self::SUCCESS;
    }
}
