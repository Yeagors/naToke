<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\PaymentRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates the lifecycle of a payment request: create → confirm/fail.
 * Lives separate from the gateways so providers don't touch DB/balance logic.
 */
class PaymentService
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function create(User $user, float $amount, ?User $initiator = null, ?string $comment = null): PaymentRequest
    {
        $gateway = $this->gateways->default();

        // Service fee added on top: the user's balance is credited with $amount,
        // while the SBP charge is amount + fee (covers the acquiring commission).
        $feePercent = (float) config('payments.topup_fee_percent', 0);
        $chargeAmount = round($amount * (1 + $feePercent / 100), 2);

        $request = PaymentRequest::create([
            'user_id' => $user->id,
            'initiated_by' => $initiator?->id,
            'amount' => $amount,
            'charge_amount' => $chargeAmount,
            'status' => PaymentStatus::Pending,
            'gateway' => $gateway->name(),
            'comment' => $comment,
        ]);

        $gateway->init($request);

        ActivityLogger::log(
            'payments.created',
            $request,
            sprintf('Создан запрос на пополнение %s ₽ для %s через %s',
                number_format($amount, 2, '.', ' '),
                $user->full_name,
                $gateway->name()
            )
        );

        return $request->refresh();
    }

    public function confirm(PaymentRequest $request, ?int $actorId = null): PaymentRequest
    {
        // Guard against duplicate/concurrent provider webhooks (T-Bank sends the
        // notification from several servers at once). We lock the payment row and
        // re-check its status INSIDE the transaction, so exactly one caller credits.
        $didConfirm = false;

        DB::transaction(function () use ($request, $actorId, &$didConfirm) {
            $locked = PaymentRequest::whereKey($request->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PaymentStatus::Pending) {
                return; // already confirmed by a concurrent/earlier call
            }

            $user = $locked->user()->lockForUpdate()->first();
            if (! $user) {
                throw new RuntimeException('Payment user disappeared');
            }

            $amount = (float) $locked->amount;
            $newBalance = (float) $user->balance + $amount;
            $user->balance = $newBalance;
            $user->save();

            $tx = Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Deposit,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'comment' => 'Пополнение через '.($locked->gateway === 'fake' ? 'симулятор СБП' : 'СБП (T-Bank)')
                    .($locked->comment ? ' · '.$locked->comment : ''),
                'created_by' => $actorId ?? $locked->initiated_by ?? $user->id,
            ]);

            $locked->status = PaymentStatus::Confirmed;
            $locked->confirmed_at = now();
            $locked->transaction_id = $tx->id;
            $locked->save();

            // Split the incoming payment between the owners — inside the guarded
            // transaction, so it runs exactly once per payment.
            $basis = config('owners.split_basis', 'base');
            $splitAmount = $basis === 'charge'
                ? (float) ($locked->charge_amount ?: $locked->amount)
                : (float) $locked->amount;
            try {
                app(\App\Services\CompanyLedger::class)->record(
                    'income', $splitAmount, "Пополнение по СБП #{$locked->id}", 'sbp', $locked->id, $actorId
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $didConfirm = true;
        });

        $request->refresh();

        if ($didConfirm) {
            ActivityLogger::log(
                'payments.confirmed',
                $request,
                sprintf('Платёж #%d зачислен · +%s ₽ на баланс %s',
                    $request->id,
                    number_format((float) $request->amount, 2, '.', ' '),
                    $request->user->full_name
                ),
                null,
                $actorId
            );
        }

        return $request;
    }

    /**
     * Reverse a previously confirmed top-up: subtract the credited amount from the
     * user's balance, record a withdrawal, and mark the request as refunded.
     * Idempotent — only a Confirmed payment can be refunded.
     */
    public function refund(PaymentRequest $request, string $reason = '', ?int $actorId = null): PaymentRequest
    {
        if ($request->status !== PaymentStatus::Confirmed) {
            return $request; // only confirmed payments are refundable; already-refunded is a no-op
        }

        DB::transaction(function () use ($request, $actorId, $reason) {
            $user = $request->user()->lockForUpdate()->first();
            if (! $user) {
                throw new RuntimeException('Payment user disappeared');
            }

            // Reverse the base (credited) amount, not the charged amount with the fee.
            $amount = (float) $request->amount;
            $newBalance = (float) $user->balance - $amount;
            $user->balance = $newBalance;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Withdrawal,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'comment' => "Возврат пополнения #{$request->id}",
                'created_by' => $actorId ?? $request->initiated_by ?? $user->id,
            ]);

            $request->status = PaymentStatus::Refunded;
            $request->failed_reason = $reason !== '' ? $reason : 'возврат';
            $request->save();
        });

        $request->refresh();

        ActivityLogger::log(
            'payments.refunded',
            $request,
            sprintf('Возврат платежа #%d · −%s ₽ с баланса %s',
                $request->id,
                number_format((float) $request->amount, 2, '.', ' '),
                $request->user->full_name
            ),
            null,
            $actorId
        );

        return $request;
    }

    public function fail(PaymentRequest $request, string $reason = ''): PaymentRequest
    {
        if ($request->status !== PaymentStatus::Pending) {
            return $request;
        }

        $request->status = PaymentStatus::Failed;
        $request->failed_reason = $reason !== '' ? $reason : 'не указано';
        $request->save();

        ActivityLogger::log(
            'payments.failed',
            $request,
            "Платёж #{$request->id} отклонён: {$request->failed_reason}"
        );

        return $request;
    }
}
