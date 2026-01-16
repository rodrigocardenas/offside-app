<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Spatie\SlackAlerts\Facades\SlackAlert;

class SendSlackAlertOnUserRegistered
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        $webhookName = $this->resolveWebhookName();

        if (! $webhookName) {
            Log::warning('Slack webhook no configurado para registros; alerta omitida.', [
                'user_id' => $user->id ?? null,
            ]);

            return;
        }

        $slack = SlackAlert::sync();

        if ($webhookName !== 'default') {
            $slack->to($webhookName);
        }

        if ($channel = $this->registrationChannel()) {
            $slack->toChannel($channel);
        }

        $slack->message($this->buildMessage($user));
    }

    private function buildMessage($user): string
    {
        $appName = config('app.name');
        $environment = config('app.env');
        $name = $user->name ?: 'Sin nombre';
        $email = $user->email ?: 'sin email';
        $userId = $user->id ? '#' . $user->id : 'sin id';
        $ip = request()?->ip();

        $parts = array_filter([
            ":tada: Nuevo registro en {$appName} [{$environment}]",
            "Usuario {$userId}",
            "Nombre: {$name}",
            "Email: {$email}",
            $ip ? "IP: {$ip}" : null,
        ]);

        return implode(' · ', $parts);
    }

    private function resolveWebhookName(): ?string
    {
        $webhooks = config('slack-alerts.webhook_urls', []);
        $preference = ['registrations', 'deployments', 'default'];

        foreach ($preference as $name) {
            if (filled($webhooks[$name] ?? null)) {
                return $name;
            }
        }

        return null;
    }

    private function registrationChannel(): ?string
    {
        $channel = config('slack-alerts.channels.registrations');

        if (! $channel) {
            return null;
        }

        $channel = trim($channel);

        if ($channel === '') {
            return null;
        }

        if ($channel[0] === '#' || $channel[0] === '@') {
            return $channel;
        }

        return '#' . $channel;
    }
}
