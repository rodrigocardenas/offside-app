<?php

return [
    'default_webhook' => env('SLACK_ALERT_WEBHOOK_URL'),

    'webhooks' => [
        'deployments' => env('SLACK_ALERT_DEPLOYMENTS_WEBHOOK_URL') ?: env('SLACK_ALERT_WEBHOOK_URL'),
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
