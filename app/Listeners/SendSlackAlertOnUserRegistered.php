<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\SlackAlerts\Facades\SlackAlert;

class SendSlackAlertOnUserRegistered
{
    public function handle(Registered $event): void
    {
        $userData = $this->extractUserData($event->user);

        if (! $userData) {
            Log::warning('No se pudo extraer información del usuario para la alerta de registro.');

            return;
        }

        if ($this->recentlyNotified($userData['id'])) {
            return;
        }

        $webhookName = $this->resolveWebhookName();

        if (! $webhookName) {
            Log::warning('Slack webhook no configurado para registros; alerta omitida.', [
                'user_id' => $userData['id'],
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

        $slack->message($this->buildMessage($userData, request()?->ip()));
    }

    private function buildMessage(array $userData, ?string $ip): string
    {
        $appName = config('app.name');
        $environment = config('app.env');
        $name = $userData['name'] ?? 'Sin nombre';
        $email = $userData['email'] ?? 'sin email';
        $userId = $userData['id'] ? '#' . $userData['id'] : 'sin id';

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

    private function extractUserData($user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user instanceof Model) {
            if (! $user->getKey()) {
                $fresh = $user->fresh();

                if ($fresh) {
                    $user = $fresh;
                }
            }

            if (! $user) {
                return null;
            }

            return $this->mapUserAttributes($user);
        }

        if (is_object($user)) {
            return $this->mapUserAttributes((array) $user);
        }

        if (is_array($user)) {
            return $this->mapUserAttributes($user);
        }

        return null;
    }

    private function mapUserAttributes($source): array
    {
        $get = static fn ($key, $fallback = null) => data_get($source, $key, $fallback);

        return [
            'id' => $get('id'),
            'name' => $get('name')
                ?? $get('full_name')
                ?? $get('username'),
            'email' => $get('email')
                ?? $get('email_address')
                ?? $get('contact_email'),
        ];
    }

    private function recentlyNotified($userId): bool
    {
        if (! $userId) {
            return false;
        }

        $cacheKey = "slack:user-registered:{$userId}";

        return ! Cache::add($cacheKey, now()->timestamp, 300);
    }
}
