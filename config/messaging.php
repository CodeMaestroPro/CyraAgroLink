<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Outbound SMS driver
    |--------------------------------------------------------------------------
    |
    | Supported: "log", "termii"
    |
    | "log" records the SMS via the Laravel logger (safe default for local/tests).
    | "termii" sends through Termii's Nigeria SMS API.
    |
    */

    'sms_driver' => env('MESSAGING_SMS_DRIVER', 'log'),

    'termii' => [
        'api_key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'CyraAgro'),
        'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        'channel' => env('TERMII_CHANNEL', 'generic'),
    ],
];
