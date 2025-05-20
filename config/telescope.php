<?php

use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Watchers;

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Telescope watchers regardless
    | of their individual configuration, which simply provides a single
    | and convenient way to enable or disable Telescope data storage.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Telescope Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Telescope will be accessible from. If the
    | setting is null, Telescope will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('TELESCOPE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Telescope Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Telescope will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => 'telescope',

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Telescope's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Queue
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the queue connection and queue
    | which will be used to process ProcessPendingUpdate jobs. This can
    | be changed if you would prefer to use a non-default connection.
    |
    */

    'queue' => [
        'connection' => env('TELESCOPE_QUEUE_CONNECTION', null),
        'queue' => env('TELESCOPE_QUEUE', null),
        'delay' => env('TELESCOPE_QUEUE_DELAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Telescope route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        \Laravel\Telescope\Http\Middleware\Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed / Ignored Paths & Commands
    |--------------------------------------------------------------------------
    |
    | The following array lists the URI paths and Artisan commands that will
    | not be watched by Telescope. In addition to this list, some Laravel
    | commands, like migrations and queue commands, are always ignored.
    |
    */

    'only_paths' => [
        // 'api/*'
    ],

    'ignore_paths' => [
        'nova-api*',
        'horizon-api*',
        'vendor/*',
    ],

    'ignore_commands' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Watchers
    |--------------------------------------------------------------------------
    |
    | The following array lists the "watchers" that will be registered with
    | Telescope. The watchers gather the application's profile data when
    | a request or task is executed. Feel free to customize this list.
    |
    */

    'watchers' => [
        Watchers\BatchWatcher::class => env('TELESCOPE_BATCH_WATCHER', true),

        Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),

        Watchers\ClientRequestWatcher::class => env('TELESCOPE_CLIENT_REQUEST_WATCHER', true),

        Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [
                'schedule:run',
                'schedule:finish',
                'package:discover',
                'config:cache',
                'config:clear',
                'view:clear',
                'view:cache',
                'route:clear',
                'route:cache',
                'clear-compiled',
                'cache:clear',
                'cache:forget',
                'config:cache',
                'config:clear',
                'view:clear',
                'view:cache',
                'route:clear',
                'route:cache',
                'clear-compiled',
                'cache:clear',
                'cache:forget',
                'optimize:clear',
                'optimize',
                'package:discover',
                'queue:restart',
                'queue:work',
                'queue:listen',
                'queue:flush',
                'queue:failed',
                'queue:retry',
                'queue:forget',
                'queue:prune-batches',
                'queue:prune-failed-jobs',
                'queue:prune-retries',
                'schedule:run',
                'schedule:finish',
                'schedule:list',
                'schedule:test',
                'schedule:work',
                'storage:link',
                'up',
                'down',
                'env',
                'view:clear',
                'view:cache',
                'route:clear',
                'route:cache',
                'config:clear',
                'config:cache',
                'cache:clear',
                'cache:forget',
                'clear-compiled',
                'optimize:clear',
                'optimize',
                'package:discover',
                'queue:restart',
                'queue:work',
                'queue:listen',
                'queue:flush',
                'queue:failed',
                'queue:retry',
                'queue:forget',
                'queue:prune-batches',
                'queue:prune-failed-jobs',
                'queue:prune-retries',
            ],
        ],

        Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),

        Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
            'ignore' => [
                'Illuminate\Mail\Events\MessageSending',
                'Illuminate\Notifications\Events\NotificationSending',
            ],
        ],

        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),

        Watchers\GateWatcher::class => [
            'enabled' => env('TELESCOPE_GATE_WATCHER', true),
            'ignore' => [
                'Illuminate\Auth\Access\Gate::check',
                'Illuminate\Auth\Access\Gate::authorize',
            ],
        ],

        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),

        Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),

        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),

        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => [
                'eloquent.created*',
                'eloquent.updated*',
                'eloquent.deleted*',
                'eloquent.restored*',
            ],
        ],

        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),

        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'ignore_paths' => [],
            'slow' => 100,
        ],

        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),

        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
        ],

        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
