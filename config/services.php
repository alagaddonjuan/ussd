<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'africastalking' => [
        'username' => env('AT_USERNAME', 'sandbox'),
        'api_key' => env('AT_API_KEY'),
        'sender_id' => env('AT_SENDER_ID'),
        'base_url' => env('AT_BASE_URL', 'https://api.africastalking.com'),
        'dry_run' => env('AT_DRY_RUN', true),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'squad' => [
        'secret_key' => env('SQUAD_SECRET_KEY'),
        'public_key' => env('SQUAD_PUBLIC_KEY'),
        'base_url' => env('SQUAD_BASE_URL', 'https://api-d.squadco.com'),
    ],

    'ussd' => [
        'modern_lottery_url' => env('MODERN_LOTTERY_USSD_URL', 'https://api.modernlotterynigeria.com/9999/bettingW/ussd/callback'),
        'default_cost' => (float) env('USSD_DEFAULT_COST', 3.50),
        'cost_multiplier' => (float) env('USSD_COST_MULTIPLIER', 4.0),
    ],

];
