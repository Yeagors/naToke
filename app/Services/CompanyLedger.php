<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\CompanyTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a company income/expense and splits it between the owners by their
 * configured shares, moving each owner's balance accordingly.
 */
class CompanyLedger
{
    /**
     * @param  string  $type    income|expense
     */
    public function record(
        string $type,
        float $amount,
        string $comment,
        string $source = 'manual',
        ?int $paymentRequestId = null,
        ?int $actorId = null,
    ): CompanyTransaction {
        $amount = round($amount, 2);
        $sign = $type === 'income' ? 1 : -1;
        $owners = config('owners.shares', []);

        return DB::transaction(function () use ($type, $amount, $comment, $source, $paymentRequestId, $actorId, $sign, $owners) {
            $ct = CompanyTransaction::create([
                'type' => $type,
                'amount' => $amount,
                'comment' => $comment,
                'source' => $source,
                'payment_request_id' => $paymentRequestId,
                'created_by' => $actorId,
            ]);

            $splits = [];
            foreach ($owners as $o) {
                $user = User::where('login', $o['login'])->lockForUpdate()->first();
                if (! $user) {
                    continue;
                }
                $share = round($amount * (float) $o['percent'] / 100, 2);
                $user->balance = (float) $user->balance + $sign * $share;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $type === 'income' ? TransactionType::Deposit : TransactionType::Withdrawal,
                    'amount' => $share,
                    'balance_after' => $user->balance,
                    'comment' => ($type === 'income' ? 'Доля дохода' : 'Доля расхода')
                        .' ('.rtrim(rtrim(number_format((float) $o['percent'], 4, '.', ''), '0'), '.').'%): '.$comment,
                    'created_by' => $actorId,
                ]);

                $splits[] = [
                    'login' => $o['login'],
                    'name' => $user->short_name,
                    'percent' => (float) $o['percent'],
                    'amount' => $share,
                ];
            }

            $ct->splits = $splits;
            $ct->save();

            return $ct;
        });
    }
}
