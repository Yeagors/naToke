<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use Illuminate\Support\Str;

/**
 * Stand-in gateway that mimics T-Bank SBP QR flow without hitting any external
 * service. The QR payload is just our own confirmation URL — anyone scanning it
 * (or the in-app "Имитировать оплату" button) marks the request as confirmed.
 *
 * Switch to TBankPaymentGateway via config/payments.php once real credentials exist.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function init(PaymentRequest $request): void
    {
        // Mock the "external" payment id you'd get back from a real provider.
        $externalId = 'FAKE-' . strtoupper(Str::random(10));

        // What gets encoded in the QR. In real life this is the SBP qrcode.nspk.ru link.
        // Here it's just our local confirmation endpoint so scanning brings the
        // browser to a URL that completes the payment.
        $confirmUrl = route('payments.fake.confirm', ['payment' => $request->id]);

        $request->fill([
            'external_id' => $externalId,
            'qr_payload' => $confirmUrl,
            'qr_url' => $confirmUrl,
            'gateway_payload' => [
                'note' => 'Fake gateway. No real money involved.',
                'created_at' => now()->toIso8601String(),
            ],
        ])->save();
    }

    public function refreshStatus(PaymentRequest $request): void
    {
        // Fake gateway has no remote side — local status is authoritative.
        // (No-op, intentionally.)
    }

    public function handleWebhook(array $payload, ?string $rawBody = null): ?PaymentRequest
    {
        return null; // Fake gateway never receives provider webhooks.
    }
}
