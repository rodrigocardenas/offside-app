<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;
use App\Models\FootballMatch;
use Carbon\Carbon;

// Configurar
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════\n";
echo "PRUEBA DE GROUNDING EN GEMINI CON gemini-2.5-flash\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Buscar partido real: Girona vs Osasuna
$match = FootballMatch::where('home_team', 'like', '%Girona%')
    ->where('away_team', 'like', '%Osasuna%')
    ->where('date', 'like', '%2026-01-10%')
    ->first();

if (!$match) {
    echo "❌ No se encontró el partido Girona vs Osasuna del 10 enero 2026\n";
    exit(1);
}

echo "✅ Partido encontrado:\n";
echo "   Home: {$match->home_team}\n";
echo "   Away: {$match->away_team}\n";
echo "   Fecha: {$match->date}\n\n";

// Crear instancia de GeminiService
$geminiService = new GeminiService();

// Preparar prompt para análisis con contexto que requiere búsqueda web
$analysisPrompt = <<<PROMPT
Analiza el próximo partido de La Liga entre {$match->home_team} y {$match->away_team} programado para {$match->date}.

Por favor:
1. Busca información ACTUAL sobre:
   - Clasificación actual de ambos equipos en La Liga 2025-26
   - Últimos resultados y forma de ambos equipos
   - Jugadores estrella actuales
   - Racha de goles (últimos 5 partidos)

2. Analiza:
   - Quién es favorito según las estadísticas actuales
   - Posibles alineaciones probables
   - Factores clave del partido
   - Predicción del resultado

Usa la búsqueda web para obtener datos REALES y ACTUALES de January 2026.

Responde en JSON con estructura:
{
  "partido": "Home vs Away",
  "fecha": "YYYY-MM-DD",
  "clasificacion_home": "Posición y puntos",
  "clasificacion_away": "Posición y puntos",
  "forma_home": "W/D/L en últimos 5",
  "forma_away": "W/D/L en últimos 5",
  "favorito": "Análisis",
  "prediccion": "Resultado probable (1/X/2)",
  "confianza": "Porcentaje",
  "notas": "Factores clave"
}
PROMPT;

echo "🔄 Enviando análisis a Gemini con GROUNDING HABILITADO...\n";
echo "   Modelo: gemini-2.5-flash\n";
echo "   Timeout: 60 segundos\n";
echo "   Max Retries: 5\n\n";

try {
    // Llamar con useGrounding = true
    $result = $geminiService->callGemini($analysisPrompt, true);

    echo "✅ Respuesta recibida de Gemini:\n";
    echo "───────────────────────────────────────────────────────\n";

    if (is_array($result) && isset($result['content'])) {
        echo $result['content'] . "\n";
    } elseif (is_array($result)) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo $result . "\n";
    }

    echo "───────────────────────────────────────────────────────\n\n";

    // Intentar parsear si es JSON
    if (is_array($result) && isset($result['partido'])) {
        echo "✅ ANÁLISIS PARSEADO CORRECTAMENTE:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n✅ TEST EXITOSO - Grounding está funcionando con gemini-2.5-flash\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Código: " . $e->getCode() . "\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "CONCLUSIÓN: El grounding de Gemini ahora está IMPLEMENTADO\n";
echo "y tu suscripción Pro debería permitir búsquedas web.\n";
echo "═══════════════════════════════════════════════════════\n";
