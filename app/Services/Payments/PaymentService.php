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
        if ($request->status !== PaymentStatus::Pending) {
            return $request; // idempotent — confirmation already happened
        }

        DB::transaction(function () use ($request, $actorId) {
            $user = $request->user()->lockForUpdate()->first();
            if (! $user) {
                throw new RuntimeException('Payment user disappeared');
            }

            $amount = (float) $request->amount;
            $newBalance = (float) $user->balance + $amount;
            $user->balance = $newBalance;
            $user->save();

            $tx = Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Deposit,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'comment' => 'Пополнение через '.($request->gateway === 'fake' ? 'симулятор СБП' : 'СБП (T-Bank)')
                    .($request->comment ? ' · '.$request->comment : ''),
                'created_by' => $actorId ?? $request->initiated_by ?? $user->id,
            ]);

            $request->status = PaymentStatus::Confirmed;
            $request->confirmed_at = now();
            $request->transaction_id = $tx->id;
            $request->save();
        });

        $request->refresh();

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
