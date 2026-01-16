<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\SlackAlerts\Facades\SlackAlert;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->registerConsoleSlackAlerts();

            return;
        }

        $this->ensureViteManifestIsAccessible();
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('userTime', function ($expression) {
            return "<?php echo \\App\\Helpers\\DateTimeHelper::toUserTimezone({$expression}); ?>";
        });

        Blade::directive('utcTime', function ($expression) {
            return "<?php echo \\App\\Helpers\\DateTimeHelper::toUTC({$expression}); ?>";
        });
    }

    private function ensureViteManifestIsAccessible(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $viteManifestPath = public_path('build/.vite/manifest.json');

        if (File::exists($viteManifestPath) && !File::exists($manifestPath)) {
            File::copy($viteManifestPath, $manifestPath);
        }
    }

    private function registerConsoleSlackAlerts(): void
    {
        $settings = config('slack-alerts.console_notifications', []);

        if (! ($settings['enabled'] ?? false)) {
            return;
        }

        $environments = $settings['environments'] ?? ['production'];

        if (! in_array(config('app.env'), $environments, true)) {
            return;
        }

        $commands = $settings['commands'] ?? [];

        if ($commands === []) {
            return;
        }

        if (! $this->hasSlackWebhookConfigured($settings['webhook'] ?? null)) {
            return;
        }

        Event::listen(CommandFinished::class, function (CommandFinished $event) use ($commands, $settings): void {
            $command = trim((string) ($event->command ?? ''));

            if ($command === '') {
                return;
            }

            if (! $this->shouldNotifyConsoleCommand($command, $commands)) {
                return;
            }

            $status = ($event->exitCode ?? 0) === 0 ? 'completado' : 'falló';
            $emoji = ($event->exitCode ?? 0) === 0 ? ':white_check_mark:' : ':x:';
            $runtimeSeconds = $event->runtime > 0 ? number_format($event->runtime / 1000, 2) : '0.00';

            $message = sprintf(
                '%s `%s` %s en %s [%s] (%ss).',
                $emoji,
                $command,
                $status,
                config('app.name'),
                config('app.env'),
                $runtimeSeconds
            );

            $this->sendSlackConsoleMessage($message, $settings['webhook'] ?? null);
        });
    }

    private function shouldNotifyConsoleCommand(string $command, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $command)) {
                return true;
            }
        }

        return false;
    }

    private function hasSlackWebhookConfigured(?string $webhookKey): bool
    {
        $webhooks = config('slack-alerts.webhook_urls', []);

        if (filled($webhooks['default'] ?? null)) {
            return true;
        }

        if ($webhookKey && filled($webhooks[$webhookKey] ?? null)) {
            return true;
        }

        return false;
    }

    private function sendSlackConsoleMessage(string $message, ?string $webhookKey): void
    {
        $webhookName = $this->resolveWebhookName($webhookKey);

        if (! $webhookName) {
            logger()->warning('Slack webhook no configurado, alerta omitida.', [
                'requested_webhook' => $webhookKey,
            ]);

            return;
        }

        $slack = SlackAlert::sync();

        if ($webhookName !== 'default') {
            $slack->to($webhookName);
        }

        $slack->message($message);
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
}
