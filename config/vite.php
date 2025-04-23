<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vite Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where Vite dev server will be accessible when running locally.
    | This is also used to generate URLs to your assets when building for production.
    |
    */

    'dev_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),

    /*
    |--------------------------------------------------------------------------
    | Vite Build Path
    |--------------------------------------------------------------------------
    |
    | The path where Vite will place its built assets. This should match the
    | outDir setting in your vite.config.js file.
    |
    */

    'build_path' => 'build',

    /*
    |--------------------------------------------------------------------------
    | Vite Manifest Path
    |--------------------------------------------------------------------------
    |
    | The path to the Vite manifest file relative to the public directory.
    |
    */

    'manifest_path' => 'build/manifest.json',

    /*
    |--------------------------------------------------------------------------
    | Vite Config Path
    |--------------------------------------------------------------------------
    |
    | The path to your vite.config.js file relative to the project root.
    |
    */

    'config_path' => base_path('vite.config.js'),
];
