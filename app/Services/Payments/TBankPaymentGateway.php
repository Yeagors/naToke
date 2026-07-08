<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Skeleton for real T-Bank acquiring (СБП QR flow).
 *
 * Docs:  https://developer.tbank.ru/eacq/scenarios/payments/nonPCI
 *
 * Flow:
 *   1. POST {api_url}/Init       → returns PaymentId
 *   2. POST {api_url}/GetQr      → returns Data (SBP payload string, also usable as deeplink)
 *   3. We display the QR / deeplink to the user.
 *   4. POST {api_url}/GetState   → polling fallback while waiting (optional)
 *   5. T-Bank → POST our /payments/webhook/tbank with the final status.
 *
 * Filling credentials:
 *   TBANK_TERMINAL_KEY=...
 *   TBANK_PASSWORD=...
 *   TBANK_API_URL=https://securepay.tinkoff.ru/v2/   (or rest-api-test for sandbox)
 *
 * Until credentials are present this class throws — keep PAYMENTS_GATEWAY=fake.
 */
class TBankPaymentGateway implements PaymentGateway
{
    public function __construct(
        private string $terminalKey,
        private string $password,
        private string $apiUrl,
        private string $webhookSecret = '',
        private int $timeout = 15,
        private ?string $successUrl = null,
        private ?string $failUrl = null,
    ) {
        if ($this->terminalKey === '' || $this->password === '') {
            throw new RuntimeException(
                'T-Bank credentials are not configured. Fill TBANK_TERMINAL_KEY and TBANK_PASSWORD in .env, '
                .'or keep PAYMENTS_GATEWAY=fake to use the simulator.'
            );
        }
    }

    public function name(): string
    {
        return 'tbank';
    }

    public function init(PaymentRequest $request): void
    {
        // Step 1 — Init: register payment intent on T-Bank.
        $initBody = [
            'TerminalKey' => $this->terminalKey,
            // T-Bank wants kopecks (amount × 100). Charge the payable amount
            // (base + service fee); the balance is credited with the base amount.
            'Amount' => (int) round($request->payable_amount * 100),
            'OrderId' => (string) $request->id,
            'Description' => 'Пополнение баланса naToke #'.$request->id,
            'NotificationURL' => route('payments.webhook.tbank'),
        ];
        if ($this->successUrl) $initBody['SuccessURL'] = $this->successUrl;
        if ($this->failUrl)    $initBody['FailURL']    = $this->failUrl;
        $initBody['Token'] = $this->signToken($initBody);

        $initResp = $this->post('Init', $initBody);
        if (! ($initResp['Success'] ?? false)) {
            throw new RuntimeException('T-Bank Init failed: '.($initResp['Message'] ?? json_encode($initResp)));
        }
        $paymentId = (string) ($initResp['PaymentId'] ?? '');

        // Step 2 — GetQr: ask T-Bank for the SBP payload string.
        // DataType is intentionally omitted — it defaults to PAYLOAD (the SBP string),
        // which keeps the token over just {TerminalKey, PaymentId} and avoids sign mismatches.
        $qrBody = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
        ];
        $qrBody['Token'] = $this->signToken($qrBody);
        $qrResp = $this->post('GetQr', $qrBody);
        if (! ($qrResp['Success'] ?? false)) {
            throw new RuntimeException('T-Bank GetQr failed: '.($qrResp['Message'] ?? json_encode($qrResp)));
        }

        $request->fill([
            'external_id' => $paymentId,
            'qr_payload' => (string) ($qrResp['Data'] ?? ''),
            'qr_url' => (string) ($qrResp['Data'] ?? ''),
            'gateway_payload' => [
                'init' => $initResp,
                'qr' => $qrResp,
            ],
        ])->save();
    }

    public function refreshStatus(PaymentRequest $request): void
    {
        if (! $request->external_id) {
            return;
        }
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $request->external_id,
        ];
        $body['Token'] = $this->signToken($body);
        $resp = $this->post('GetState', $body);
        if (! ($resp['Success'] ?? false)) {
            return; // Keep current local state.
        }
        $this->applyExternalStatus($request, (string) ($resp['Status'] ?? ''), $resp);
    }

    public function cancel(PaymentRequest $request): array
    {
        if (! $request->external_id) {
            throw new RuntimeException('No T-Bank PaymentId to cancel.');
        }
        // POST /Cancel — full cancel/refund of the payment identified by PaymentId.
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $request->external_id,
        ];
        $body['Token'] = $this->signToken($body);
        $resp = $this->post('Cancel', $body);
        if (! ($resp['Success'] ?? false)) {
            throw new RuntimeException('T-Bank Cancel failed: '.($resp['Message'] ?? json_encode($resp)));
        }
        // Reflect the resulting status locally right away. A REFUNDED/REVERSED/CANCELED
        // notification may also arrive later; PaymentService methods are idempotent.
        $this->applyExternalStatus($request, (string) ($resp['Status'] ?? 'REFUNDED'), $resp);

        return $resp;
    }

    public function handleWebhook(array $payload, ?string $rawBody = null): ?PaymentRequest
    {
        // T-Bank signs notification with the same Token scheme. Verify it before trusting.
        $incomingToken = (string) ($payload['Token'] ?? '');
        $check = $payload;
        unset($check['Token']);
        if ($this->signToken($check) !== $incomingToken) {
            Log::warning('TBank webhook with invalid Token', ['payload' => $payload]);
            return null;
        }

        // OrderId == PaymentRequest->id (we passed it in Init).
        $orderId = (int) ($payload['OrderId'] ?? 0);
        $request = $orderId ? PaymentRequest::find($orderId) : null;
        if (! $request) {
            return null;
        }

        $this->applyExternalStatus($request, (string) ($payload['Status'] ?? ''), $payload);
        return $request;
    }

    private function applyExternalStatus(PaymentRequest $request, string $externalStatus, array $raw): void
    {
        // T-Bank status reference (subset):
        //   NEW, FORM_SHOWED, AUTHORIZING, AUTHORIZED, CONFIRMING, CONFIRMED,
        //   REVERSING, REVERSED, REFUNDING, REFUNDED, REJECTED, DEADLINE_EXPIRED, CANCELED
        $status = strtoupper($externalStatus);

        // Stash the latest provider payload for traceability.
        $request->gateway_payload = array_merge((array) $request->gateway_payload, [
            'last_remote_status' => $externalStatus,
            'last_remote_payload' => $raw,
        ]);
        $request->save();

        // Balance/audit changes are delegated to PaymentService so they happen in one
        // place. All service methods are idempotent (guard on current status).
        $service = app(\App\Services\Payments\PaymentService::class);

        if (in_array($status, ['CONFIRMED', 'AUTHORIZED'], true)) {
            $service->confirm($request);
        } elseif (in_array($status, ['REFUNDED', 'REVERSED'], true)) {
            // Full refund/reversal: reverse the balance if we had credited it,
            // otherwise treat a not-yet-credited payment as failed.
            if ($request->status === PaymentStatus::Confirmed) {
                $service->refund($request, $status);
            } else {
                $service->fail($request, $status);
            }
        } elseif (in_array($status, ['REJECTED', 'DEADLINE_EXPIRED', 'CANCELED', 'CANCELLED'], true)) {
            $service->fail($request, $status);
        }
    }

    private function post(string $method, array $body): array
    {
        $url = rtrim($this->apiUrl, '/').'/'.ltrim($method, '/');
        return Http::timeout($this->timeout)
            ->asJson()
            ->acceptJson()
            ->post($url, $body)
            ->throw()
            ->json() ?? [];
    }

    /**
     * T-Bank token: take all top-level string/number params (no nested objects),
     * append Password, sort by key, concatenate values, SHA256 hex.
     * https://developer.tbank.ru/eacq/api-reference/sign-request
     */
    private function signToken(array $body): string
    {
        $flat = [];
        foreach ($body as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $flat[$k] = $v === false ? 'false' : ($v === true ? 'true' : (string) $v);
            }
        }
        $flat['Password'] = $this->password;
        ksort($flat);
        return hash('sha256', implode('', $flat));
    }
}
