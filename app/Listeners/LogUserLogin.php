<?php

namespace App\Listeners;

use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogUserLogin implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 1;

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        try {
            $request = request();
            $agent = $request?->userAgent();
            $device = $request?->header('X-Device-Name') ?? $this->detectDevice($agent);

            UserLogin::create([
                'user_id' => $user->id,
                'ip_address' => $request?->ip(),
                'device' => $device,
                'user_agent' => $agent,
                'logged_in_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to log user login event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function detectDevice(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        if (Str::contains($userAgent, 'iPhone')) {
            return 'iPhone';
        }

        if (Str::contains($userAgent, 'iPad')) {
            return 'iPad';
        }

        if (Str::contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (Str::contains($userAgent, 'Mac OS X')) {
            return 'macOS';
        }

        if (Str::contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        return 'Otro';
    }
}
