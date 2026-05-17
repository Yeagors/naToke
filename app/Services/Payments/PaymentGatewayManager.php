<?php

namespace App\Services\Payments;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Builds the configured PaymentGateway. Registered as a singleton in
 * App\Providers\AppServiceProvider so config is read once per request.
 */
class PaymentGatewayManager
{
    private ?PaymentGateway $cached = null;

    public function __construct(
        private Container $app,
        private array $config,
    ) {}

    public function default(): PaymentGateway
    {
        if ($this->cached) {
            return $this->cached;
        }
        return $this->cached = $this->make($this->config['default'] ?? 'fake');
    }

    public function make(string $name): PaymentGateway
    {
        $cfg = $this->config['gateways'][$name] ?? null;
        if (! $cfg) {
            throw new InvalidArgumentException("Unknown payment gateway: {$name}");
        }

        return match ($cfg['driver']) {
            'fake' => new FakePaymentGateway(),
            'tbank' => new TBankPaymentGateway(
                terminalKey: (string) ($cfg['terminal_key'] ?? ''),
                password: (string) ($cfg['password'] ?? ''),
                apiUrl: (string) ($cfg['api_url'] ?? ''),
                webhookSecret: (string) ($cfg['webhook_secret'] ?? ''),
                timeout: (int) ($cfg['http_timeout'] ?? 15),
                successUrl: $cfg['success_url'] ?? null,
                failUrl: $cfg['fail_url'] ?? null,
            ),
            default => throw new InvalidArgumentException("Unsupported driver: {$cfg['driver']}"),
        };
    }
}
