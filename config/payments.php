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
    | Top-up service fee (percent)
    |--------------------------------------------------------------------------
    |
    | Added on top of the requested amount to cover the acquiring commission.
    | The user's balance is credited with the requested amount; the SBP charge
    | is amount × (1 + fee/100). Example: fee 3, request 1000 → charge 1030,
    | balance +1000.
    |
    */
    'topup_fee_percent' => (float) env('PAYMENTS_TOPUP_FEE_PERCENT', 3),

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

            // Онлайн-касса (54-ФЗ). Обязательно, если к терминалу подключена касса
            // (иначе Init отвечает ErrorCode 309 {request.validate.expected.receipt}).
            'receipt' => [
                'enabled' => (bool) env('TBANK_RECEIPT_ENABLED', false),
                // Система налогообложения: osn | usn_income | usn_income_outcome | patent | envd | esn
                'taxation' => env('TBANK_RECEIPT_TAXATION', 'usn_income'),
                // Ставка НДС: none | vat0 | vat5 | vat7 | vat10 | vat20 | vat110 | vat120
                'vat' => env('TBANK_RECEIPT_VAT', 'none'),
                'item_name' => env('TBANK_RECEIPT_ITEM', 'Пополнение баланса'),
                // Признак способа/предмета расчёта
                'payment_method' => env('TBANK_RECEIPT_PAYMENT_METHOD', 'full_payment'),
                'payment_object' => env('TBANK_RECEIPT_PAYMENT_OBJECT', 'service'),
                // Куда отправить чек, если у пользователя не заполнен телефон.
                'default_email' => env('TBANK_RECEIPT_DEFAULT_EMAIL', ''),
            ],
        ],

    ],
];
