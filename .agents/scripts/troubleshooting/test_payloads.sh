ssh -o StrictHostKeyChecking=no -i ~/.ssh/key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com "cat > /var/www/html/test_payloads.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\User;
use Kreait\Firebase\Factory;

// Probar enviando a user 249 y 251
\$users = User::whereIn('id', [249, 251])->get();

\$credentials_path = base_path('storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json');
\$factory = (new Factory)->withServiceAccount(\$credentials_path);
\$messaging = \$factory->createMessaging();

foreach (\$users as \$user) {
    foreach (\$user->pushSubscriptions as \$sub) {
        
        // 1. Payload SIMPLE (como Firebase Console)
        \$msgSimple = [
            'notification' => [
                'title' => 'Prueba SIMPLE',
                'body' => 'Sin bloque android/apns',
            ],
            'token' => \$sub->device_token,
        ];
        
        // 2. Payload COMPLETO (el actual en el código)
        \$msgCompleto = [
            'notification' => [
                'title' => 'Prueba COMPLETA',
                'body' => 'Con bloque android/apns',
            ],
            'data' => ['type' => 'test'],
            'token' => \$sub->device_token,
        ];
        
        if (in_array(\$sub->platform, ['android', 'ios'])) {
            \$msgCompleto['android'] = [
                'priority' => 'high',
                'notification' => [
                    'channelId' => 'high_importance_channel',
                    'title' => 'Prueba COMPLETA',
                    'body' => 'Con bloque android/apns',
                    'icon' => 'ic_notification',
                ],
            ];
            \$msgCompleto['apns'] = [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => 'Prueba COMPLETA',
                            'body' => 'Con bloque android/apns',
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                    'mutableContent' => true,
                ],
            ];
        }
        
        try {
            \$messaging->send(\$msgSimple);
            echo \"Sent SIMPLE to {\$sub->platform} user {\$user->id}\n\";
        } catch (\Throwable \$e) {
            echo \"Error SIMPLE: \" . \$e->getMessage() . \"\n\";
        }
        
        try {
            \$messaging->send(\$msgCompleto);
            echo \"Sent COMPLETO to {\$sub->platform} user {\$user->id}\n\";
        } catch (\Throwable \$e) {
            echo \"Error COMPLETO: \" . \$e->getMessage() . \"\n\";
        }
    }
}
EOF
php /var/www/html/test_payloads.php"
