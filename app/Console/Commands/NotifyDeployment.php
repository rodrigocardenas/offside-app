<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\SlackAlerts\Facades\SlackAlert;

class NotifyDeployment extends Command
{
    protected $signature = 'deployment:notify
                            {status=success : success|failed}
                            {--branch= : Rama desplegada}
                            {--env= : Entorno objetivo}
                            {--channel=deployments : Nombre del webhook configurado}
                            {--slack-channel= : Canal de Slack (ej. #app-notifications)}
                            {--initiator= : Usuario que lanzó el deploy}
                            {--commit= : Hash corto del commit desplegado}
                            {--summary= : Mensaje adicional (ej. título del commit)}';

    protected $description = 'Envía una alerta a Slack cuando termina el despliegue';

    public function handle(): int
    {
        $requestedWebhook = $this->option('channel') ?: null;
        $slackChannel = $this->normalizeSlackChannel($this->option('slack-channel'));
        $webhookName = $this->resolveWebhookName($requestedWebhook);

        if (! $webhookName) {
            $this->error('No hay ningún webhook de Slack configurado. Se omite la alerta.');

            return self::FAILURE;
        }

        $status = Str::of($this->argument('status'))->lower()->value();
        $successful = $status === 'success';

        $appName = config('app.name');
        $environment = $this->option('env') ?: config('app.env');
        $branch = $this->option('branch') ?: 'desconocida';
        $initiator = $this->option('initiator') ?: get_current_user();
        $commit = $this->option('commit');
        $summary = $this->option('summary');

        $emoji = $successful ? ':rocket:' : ':warning:';
        $statusText = $successful ? 'exitoso' : 'con problemas';

        $parts = [
            sprintf('%s Deploy %s para %s (%s)', $emoji, $statusText, $appName, $environment),
            "rama $branch",
        ];

        if ($commit) {
            $parts[] = "commit $commit";
        }

        if ($initiator) {
            $parts[] = "lanzado por $initiator";
        }

        if ($summary) {
            $parts[] = "nota: $summary";
        }

        $message = implode(' · ', $parts);

        $slack = SlackAlert::sync();

        if ($webhookName !== 'default') {
            $slack->to($webhookName);
        }

        if ($slackChannel) {
            $slack->toChannel($slackChannel);
        }

        if ($requestedWebhook && $requestedWebhook !== $webhookName) {
            $this->warn("El webhook '$requestedWebhook' no está configurado, se usa '$webhookName'.");
        }

        $slack->message($message);

        $this->info('Alerta enviada a Slack.');

        return self::SUCCESS;
    }

    private function resolveWebhookName(?string $requested): ?string
    {
        $webhooks = config('slack-alerts.webhook_urls', []);

        if ($requested && filled($webhooks[$requested] ?? null)) {
            return $requested;
        }

        if (filled($webhooks['default'] ?? null)) {
            return 'default';
        }

        return null;
    }

    private function normalizeSlackChannel(?string $channel): ?string
    {
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

        return '#'.$channel;
    }
}
