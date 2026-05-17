<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Switch to "tbank" once T-Bank acquiring credentials are filled in.
    | Until then, "fake" lets the UI run end-to-end (QR shown, simulate-payment
    | button completes it) so the team can demo flow without real money.
    |
    */
    'default' => env('PAYMENTS_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Top-up bounds (rubles)
    |--------------------------------------------------------------------------
    */
    'min_amount' => env('PAYMENTS_MIN', 50),
    'max_amount' => env('PAYMENTS_MAX', 100000),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    */
    'gateways' => [

        'fake' => [
            'driver' => 'fake',
        ],

        'tbank' => [
            'driver' => 'tbank',
            // Fill these from the T-Bank merchant cabinet:
            //   https://developer.tbank.ru/eacq/scenarios/payments/nonPCI
            'terminal_key' => env('TBANK_TERMINAL_KEY', ''),
            'password' => env('TBANK_PASSWORD', ''),
            // Test API:  https://rest-api-test.tinkoff.ru/v2/
            // Prod API:  https://securepay.tinkoff.ru/v2/
            'api_url' => env('TBANK_API_URL', 'https://securepay.tinkoff.ru/v2/'),
            // Optional shared secret for notification verification; T-Bank signs
            // notifications with the same password-based token scheme as requests.
            'webhook_secret' => env('TBANK_WEBHOOK_SECRET', ''),
            'success_url' => env('TBANK_SUCCESS_URL', ''),
            'fail_url' => env('TBANK_FAIL_URL', ''),
            'http_timeout' => 15,
        ],

    ],
];
