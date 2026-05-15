<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fiscal' => [
        'enabled' => (bool) env('FISCAL_ENABLED', false),
        'provider' => env('FISCAL_PROVIDER', ''),
        'provider_name' => env('FISCAL_API_NAME', ''),
        'series' => env('FISCAL_SERIE', '1'),
        'api_url' => env('FISCAL_API_URL', ''),
        'token' => env('FISCAL_API_TOKEN', ''),
        'auth_header' => env('FISCAL_API_AUTH_HEADER', 'Authorization'),
        'auth_prefix' => env('FISCAL_API_AUTH_PREFIX', 'Bearer'),
        'timeout' => (int) env('FISCAL_TIMEOUT', 15),
    ],

];
