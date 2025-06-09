<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $serverKey;
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
    }

    public function sendPushNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => '/images/icon-192x192.png',
                    'click_action' => $data['url'] ?? '/',
                ],
                'data' => $data,
            ]);

            if (!$response->successful()) {
                Log::error('Error al enviar notificación push', [
                    'response' => $response->json(),
                    'device_token' => $deviceToken
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Excepción al enviar notificación push', [
                'error' => $e->getMessage(),
                'device_token' => $deviceToken
            ]);
            return false;
        }
    }
}
