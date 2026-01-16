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
                            {--channel=deployments : Canal configurado en slack-alerts.php}
                            {--initiator= : Usuario que lanzó el deploy}
                            {--commit= : Hash corto del commit desplegado}';

    protected $description = 'Envía una alerta a Slack cuando termina el despliegue';

    public function handle(): int
    {
        $channel = $this->option('channel') ?: null;

        if (! $this->slackWebhookDisponible($channel)) {
            $this->warn('No hay webhook de Slack configurado. Se omite la alerta.');

            return self::SUCCESS;
        }

        $status = Str::of($this->argument('status'))->lower();
        $successful = $status === 'success';

        $appName = config('app.name');
        $environment = $this->option('env') ?: config('app.env');
        $branch = $this->option('branch') ?: 'desconocida';
        $initiator = $this->option('initiator') ?: get_current_user();
        $commit = $this->option('commit');

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

        $message = implode(' · ', $parts);

        $channel
            ? SlackAlert::to($channel)->message($message)
            : SlackAlert::message($message);

        $this->info('Alerta enviada a Slack.');

        return self::SUCCESS;
    }

    private function slackWebhookDisponible(?string $channel): bool
    {
        $webhooks = config('slack-alerts.webhook_urls', []);

        if (filled($webhooks['default'] ?? null)) {
            return true;
        }

        if ($channel && filled($webhooks[$channel] ?? null)) {
            return true;
        }

        return false;
    }
}
