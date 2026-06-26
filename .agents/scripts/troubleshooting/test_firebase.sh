ssh -o StrictHostKeyChecking=no -i ~/.ssh/key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com "cat > /var/www/html/test_firebase_direct.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\ChatMessage;
use App\Models\Group;
use Kreait\Firebase\Factory;

\$msg = ChatMessage::find(289);
\$group = \$msg->group;
\$groupUsers = \$group->users;

\$credentials_path = base_path('storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json');
\$factory = (new Factory)->withServiceAccount(\$credentials_path);
\$messaging = \$factory->createMessaging();

foreach (\$groupUsers as \$user) {
    if (\$user->id == \$msg->user_id) continue;
    
    foreach (\$user->pushSubscriptions as \$sub) {
        \$message = [
            'notification' => [
                'title' => 'Test',
                'body' => 'Test body',
            ],
            'data' => [],
            'token' => \$sub->device_token,
        ];
        
        echo \"Enviando a token: {\$sub->device_token}\n\";
        try {
            \$messaging->send(\$message);
            echo \"OK!\n\";
        } catch (\Throwable \$e) {
            echo \"ERROR Kreait: \" . \$e->getMessage() . \"\n\";
        }
    }
}
EOF
php /var/www/html/test_firebase_direct.php"
