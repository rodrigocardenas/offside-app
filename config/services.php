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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'football_data' => [
        // Token específico de Football-Data.org
        'api_key' => env('FOOTBALL_DATA_API_KEY', env('FOOTBALL_DATA_API_TOKEN')),
        // RapidAPI (api-football) token
        'api_token' => env('FOOTBALL_API_KEY'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'football' => [
        'key' => env('FOOTBALL_API_KEY'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

];
