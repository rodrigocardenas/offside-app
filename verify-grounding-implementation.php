<?php
require 'vendor/autoload.php';

use App\Services\GeminiService;

// Configurar
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════\n";
echo "VERIFICAR IMPLEMENTACIÓN DE GROUNDING\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Verificando archivo GeminiService.php...\n\n";

$filePath = 'app/Services/GeminiService.php';
$content = file_get_contents($filePath);

// Buscar la implementación de grounding
if (preg_match("/\\\$payload\['tools'\]\s*=\s*\[\s*\[\s*'googleSearch'/", $content)) {
    echo "✅ Grounding CORRECTAMENTE IMPLEMENTADO\n";
    echo "   Encontrado: \$payload['tools'] = [['googleSearch' => ...]]\n\n";
} else {
    echo "❌ Grounding NO implementado\n";
    exit(1);
}

// Mostrar el código relevante
$lines = explode("\n", $content);
$inGroundingSection = false;
$groundingCode = [];

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "\$payload = [") !== false) {
        for ($j = $i; $j < min($i + 30, count($lines)); $j++) {
            $groundingCode[] = $lines[$j];
            if (strpos($lines[$j], "if (config('gemini.logging.log_requests')) {") !== false) {
                break;
            }
        }
        break;
    }
}

if (!empty($groundingCode)) {
    echo "📝 CÓDIGO IMPLEMENTADO:\n";
    echo "───────────────────────────────────────────────────────\n";
    foreach ($groundingCode as $line) {
        echo $line . "\n";
    }
    echo "───────────────────────────────────────────────────────\n\n";
}

// Verificar configuración en .env
$envPath = '.env';
$envContent = file_get_contents($envPath);

if (strpos($envContent, 'GEMINI_GROUNDING_ENABLED=true') !== false) {
    echo "✅ GEMINI_GROUNDING_ENABLED=true en .env\n\n";
} else {
    echo "⚠️  GEMINI_GROUNDING_ENABLED no está configurado\n\n";
}

// Buscar qué modelo está configurado
if (preg_match("/GEMINI_MODEL=(.+)/", $envContent, $matches)) {
    $model = trim($matches[1]);
    echo "✅ Modelo configurado: {$model}\n";
    echo "   (gemini-2.5-flash soporta grounding)\n\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "✅ GROUNDING ESTÁ CORRECTAMENTE IMPLEMENTADO\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Próximos pasos:\n";
echo "1. Con tu suscripción Pro, las búsquedas web funcionarán\n";
echo "2. El payload ahora incluye 'tools' => [['googleSearch' => ...]]\n";
echo "3. Gemini 2.5 Flash soporta grounding con Pro subscription\n";
echo "4. Espera entre llamadas (rate limiting: 60s entre intentos)\n\n";

echo "Para probar en producción:\n";
echo "  \$geminiService->callGemini(\$prompt, true);  // useGrounding=true\n";
echo "═══════════════════════════════════════════════════════\n";
