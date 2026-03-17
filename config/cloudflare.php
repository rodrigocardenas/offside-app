<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Images Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudflare Images integration
    | Used for optimized image delivery and storage
    |
    */

    'enabled' => env('CLOUDFLARE_IMAGES_ENABLED', false),

    'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),

    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    'api_url' => 'https://api.cloudflare.com/client/v4',

    'images_domain' => env('CLOUDFLARE_IMAGES_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | If Cloudflare Images is unavailable, fall back to local storage
    |
    */

    'fallback_disk' => env('CLOUDFLARE_IMAGES_FALLBACK_DISK', 'public'),

    'enable_fallback' => env('CLOUDFLARE_IMAGES_ENABLE_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for image uploads to Cloudflare
    |
    */

    'upload' => [
        'timeout' => env('CLOUDFLARE_UPLOAD_TIMEOUT', 30),
        'retries' => env('CLOUDFLARE_UPLOAD_RETRIES', 3),
        'retry_delay' => env('CLOUDFLARE_UPLOAD_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Transformations
    |--------------------------------------------------------------------------
    |
    | Predefined image transformation presets
    |
    */

    'transforms' => [
        'avatar_small' => [
            'width' => 120,
            'height' => 120,
            'crop' => 'cover',
            'quality' => 'auto',
        ],
        'avatar_medium' => [
            'width' => 400,
            'height' => 400,
            'crop' => 'cover',
            'quality' => 'auto',
        ],
        'logo' => [
            'width' => 200,
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'group_cover' => [
            'width' => 1920,
            'height' => 1080,
            'crop' => 'cover',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'group_cover_mobile' => [
            'width' => 768,
            'height' => 512,
            'crop' => 'cover',
            'quality' => 'auto',
            'format' => 'auto',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoints
    |--------------------------------------------------------------------------
    |
    | Widths to generate for responsive srcset
    |
    */

    'responsive_widths' => [
        'avatar' => [120, 240, 400],
        'logo' => [100, 200, 400],
        'group_cover' => [768, 1024, 1920],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache configuration for image URLs
    |
    */

    'cache' => [
        'enabled' => env('CLOUDFLARE_CACHE_ENABLED', true),
        'ttl' => env('CLOUDFLARE_CACHE_TTL', 86400), // seconds (1 day)
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log Cloudflare Images operations
    |
    */

    'logging' => [
        'enabled' => env('CLOUDFLARE_LOGGING_ENABLED', true),
        'channel' => env('CLOUDFLARE_LOG_CHANNEL', 'stack'),
    ],

];
