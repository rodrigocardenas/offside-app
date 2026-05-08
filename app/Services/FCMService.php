<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

/**
 * FCMService - Firebase Cloud Messaging Service
 * 
 * Servicio para enviar notificaciones push usando Firebase Cloud Messaging (FCM)
 * Utiliza la API HTTP v1 de Google (versión actual, no deprecated)
 * 
 * Cambios desde la versión legacy:
 * - ✅ Migrado de fcm/send a v1/projects/{id}/messages:send
 * - ✅ Autenticación con OAuth 2.0 (Bearer token) en lugar de API Key
 * - ✅ Usa Kreait Firebase Factory que maneja OAuth2 automáticamente
 * - ✅ Formato de mensaje actualizado al estándar v1
 * - ✅ Mejor manejo de errores y logging
 */
class FCMService
{
    protected $messaging;
    protected $credentialsPath;

    public function __construct()
    {
        $this->credentialsPath = base_path("storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json");
        $this->initializeFirebaseMessaging();
    }

    /**
     * Inicializa el cliente de Firebase Messaging usando credenciales OAuth 2.0
     * 
     * @throws \Exception Si el archivo de credenciales no existe o hay error de inicialización
     */
    protected function initializeFirebaseMessaging()
    {
        if (!file_exists($this->credentialsPath)) {
            Log::error('❌ Archivo de credenciales de Firebase no encontrado', [
                'path' => $this->credentialsPath
            ]);
            throw new \Exception('Firebase credentials not found at: ' . $this->credentialsPath);
        }

        try {
            $factory = (new Factory)->withServiceAccount($this->credentialsPath);
            $this->messaging = $factory->createMessaging();
            Log::info('✅ Firebase Messaging inicializado correctamente con HTTP v1 API');
        } catch (\Throwable $e) {
            Log::error('❌ Error al inicializar Firebase Messaging', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Envía una notificación push a un dispositivo específico
     * 
     * @param string $deviceToken Token del dispositivo (FCM device token)
     * @param string $title Título de la notificación
     * @param string $body Cuerpo de la notificación
     * @param array $data Datos adicionales a enviar (opcional)
     * @param string $platform Plataforma del dispositivo (web|android|ios) - default: web
     * @return bool true si se envió exitosamente, false en caso de error
     */
    public function sendPushNotification(
        $deviceToken,
        $title,
        $body,
        $data = [],
        $platform = 'web'
    ) {
        try {
            // Validar token
            if (empty($deviceToken) || strlen($deviceToken) < 50) {
                Log::warning('⚠️  Token de dispositivo inválido o muy corto', [
                    'token_length' => strlen($deviceToken),
                    'platform' => $platform
                ]);
                return false;
            }

            // Construir mensaje en formato FCM HTTP v1
            $message = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'token' => $deviceToken,
            ];

            // Agregar opciones específicas por plataforma
            if ($platform === 'android') {
                $message['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'channelId' => 'high_importance_channel',
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'ic_notification',
                        // No clickAction: dejar que FCM/Capacitor abra la app por defecto
                    ],
                ];
            } elseif ($platform === 'ios') {
                $message['apns'] = [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                        'mutableContent' => true,
                    ],
                ];
            } else {
                // Web
                $message['webpush'] = [
                    'headers' => [
                        'Urgency' => 'high',
                    ],
                    'notification' => [
                        'icon' => '/images/logo_white_bg.png',
                        'click_action' => $data['link'] ?? '/',
                    ],
                    'fcm_options' => [
                        'link' => $data['link'] ?? '/',
                    ],
                ];
            }

            // Enviar usando Kreait Firebase (HTTP v1 automáticamente)
            $this->messaging->send($message);

            Log::info('✅ Notificación push enviada exitosamente', [
                'platform' => $platform,
                'title' => $title,
                'token_preview' => substr($deviceToken, 0, 20) . '...',
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('❌ Error al enviar notificación push', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'title' => $title,
                'token_preview' => substr($deviceToken, 0, 20) . '...',
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Envía notificación a múltiples dispositivos
     * 
     * @param array $deviceTokens Array de tokens de dispositivos
     * @param string $title Título de la notificación
     * @param string $body Cuerpo de la notificación
     * @param array $data Datos adicionales
     * @param string $platform Plataforma
     * @return array ['success' => int, 'failed' => int]
     */
    public function sendPushNotificationBatch(
        array $deviceTokens,
        $title,
        $body,
        $data = [],
        $platform = 'web'
    ) {
        $success = 0;
        $failed = 0;

        foreach ($deviceTokens as $token) {
            if ($this->sendPushNotification($token, $title, $body, $data, $platform)) {
                $success++;
            } else {
                $failed++;
            }
        }

        Log::info('📊 Envío batch de notificaciones completado', [
            'total' => count($deviceTokens),
            'success' => $success,
            'failed' => $failed,
            'platform' => $platform
        ]);

        return ['success' => $success, 'failed' => $failed];
    }
}
