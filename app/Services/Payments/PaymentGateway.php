<?php

namespace App\Services\Payments;

use App\Models\PaymentRequest;

/**
 * Bridge between our app and a real payment provider.
 * Concrete implementations: FakePaymentGateway, TBankPaymentGateway.
 */
interface PaymentGateway
{
    /**
     * Register the payment on the provider side and persist any returned
     * identifiers / QR payload onto the PaymentRequest model.
     */
    public function init(PaymentRequest $request): void;

    /**
     * Pull the latest status from the provider; updates the PaymentRequest
     * in place if status changed.
     */
    public function refreshStatus(PaymentRequest $request): void;

    /**
     * Handle a notification callback (webhook) from the provider.
     * Returns the matched PaymentRequest or null when the payload can't be matched.
     */
    public function handleWebhook(array $payload, ?string $rawBody = null): ?PaymentRequest;

    /**
     * Provider key for logging/storage ("fake" / "tbank").
     */
    public function name(): string;
}
