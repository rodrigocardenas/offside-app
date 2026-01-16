<?php

use Spatie\SlackAlerts\Jobs\SendToSlackChannelJob;

return [
    'enabled' => env('SLACK_ALERT_ENABLED', true),

    'webhook_urls' => [
        'default' => env('SLACK_ALERT_WEBHOOK_URL', env('SLACK_ALERT_WEBHOOK')),
        'deployments' => env('SLACK_ALERT_DEPLOYMENTS_WEBHOOK_URL')
            ?: env('SLACK_ALERT_WEBHOOK_URL', env('SLACK_ALERT_WEBHOOK')),
        'registrations' => env('SLACK_ALERT_REGISTRATIONS_WEBHOOK_URL')
            ?: env('SLACK_ALERT_DEPLOYMENTS_WEBHOOK_URL')
            ?: env('SLACK_ALERT_WEBHOOK_URL', env('SLACK_ALERT_WEBHOOK')),
    ],

    'job' => SendToSlackChannelJob::class,

    'queue' => env('SLACK_ALERT_QUEUE', 'default'),

    'channels' => [
        'registrations' => env('SLACK_ALERT_REGISTRATIONS_CHANNEL'),
    ],

    'console_notifications' => [
        'enabled' => env('SLACK_ALERT_CONSOLE_ENABLED', true),
        'webhook' => env('SLACK_ALERT_CONSOLE_WEBHOOK', 'deployments'),
        'environments' => ['production'],
        'commands' => [
            'migrate*',
            'db:seed*',
        ],
    ],
];
