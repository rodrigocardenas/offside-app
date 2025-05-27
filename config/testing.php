<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Testing Database Connection
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use while running your tests. The default is sqlite in memory.
    |
    */

    'database' => [
        'connection' => 'sqlite',
        'database' => ':memory:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Cache Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the cache drivers below you wish
    | to use while running your tests. The default is array.
    |
    */

    'cache' => [
        'driver' => 'array',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Queue Connection
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the queue connections below you wish
    | to use while running your tests. The default is sync.
    |
    */

    'queue' => [
        'connection' => 'sync',
    ],
];
