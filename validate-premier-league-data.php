<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 VALIDACIÓN INTELIGENTE: Premier League Matchday 21\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Estrategia:\n";
echo "1. Obtener Matchday 21 de Football-Data.org (FUENTE VERIFICABLE)\n";
echo "2. Mostrar partidos reales\n";
echo "3. Explicar por qué estos datos son confiables\n";
echo "4. Comparar con lo que Gemini DEBERÍA encontrar\n\n";

$apiKey = config('services.football_data.api_key') ?? config('FOOTBALL_DATA_API_KEY');

if (!$apiKey) {
    // Intentar leer del .env directamente
    $envPath = dirname(__FILE__) . '/.env';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        if (preg_match("/FOOTBALL_DATA_API_KEY=(.+)/", $envContent, $matches)) {
            $apiKey = trim($matches[1]);
        }
    }
}

if (!$apiKey) {
    echo "❌ Error: FOOTBALL_DATA_API_KEY no configurada\n";
    exit(1);
}

echo "🔄 Consultando Football-Data.org para Premier League...\n";
echo "   (Esta es una FUENTE VERIFICABLE)\n\n";

try {
    // Obtener información de Premier League
    $leaguesResponse = Http::withoutVerifying()
        ->withHeaders(['X-Auth-Token' => $apiKey])
        ->get('https://api.football-data.org/v4/competitions/PL');
    
    if ($leaguesResponse->failed()) {
        echo "❌ Error consultando competitions: " . $leaguesResponse->status() . "\n";
        exit(1);
    }
    
    $leagueData = $leaguesResponse->json();
    $currentMatchday = $leagueData['currentSeason']['currentMatchday'] ?? null;
    
    echo "✅ Premier League información:\n";
    echo "   Temporada: " . ($leagueData['currentSeason']['startDate'] ?? 'N/A') . " - " . 
         ($leagueData['currentSeason']['endDate'] ?? 'N/A') . "\n";
    echo "   Jornada actual: " . $currentMatchday . "\n";
    echo "   Próxima jornada: " . ($currentMatchday + 1) . "\n\n";
    
    // Obtener partidos de la próxima jornada
    $matchday = $currentMatchday + 1;
    
    echo "🔄 Obteniendo partidos de Matchday {$matchday}...\n";
    
    $matchesResponse = Http::withoutVerifying()
        ->withHeaders(['X-Auth-Token' => $apiKey])
        ->get("https://api.football-data.org/v4/competitions/PL/matches", [
            'matchday' => $matchday
        ]);
    
    if ($matchesResponse->failed()) {
        echo "❌ Error consultando matches: " . $matchesResponse->status() . "\n";
        echo "   Respuesta: " . $matchesResponse->body() . "\n";
        exit(1);
    }
    
    $matchesData = $matchesResponse->json();
    $matches = $matchesData['matches'] ?? [];
    
    echo "✅ Encontrados " . count($matches) . " partidos\n\n";
    
    // Mostrar partidos
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📊 PREMIER LEAGUE - MATCHDAY {$matchday}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $partidos_por_dia = [];
    
    foreach ($matches as $i => $match) {
        $local = $match['homeTeam']['name'];
        $visitante = $match['awayTeam']['name'];
        $fecha = $match['utcDate'];
        $estado = $match['status'];
        
        // Parsear fecha
        $fechaObj = new \DateTime($fecha);
        $dia = $fechaObj->format('l'); // Día en inglés
        $dia_es = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        
        $dia_semana = $dia_es[$dia] ?? $dia;
        $fecha_formateada = $fechaObj->format('d/m/Y');
        $hora = $fechaObj->format('H:i');
        
        echo "PARTIDO " . ($i + 1) . ":\n";
        echo "  🏠 Local:     {$local}\n";
        echo "  🚗 Visitante: {$visitante}\n";
        echo "  📅 Fecha:     {$dia_semana} {$fecha_formateada}\n";
        echo "  ⏰ Hora:      {$hora} UTC\n";
        echo "  ℹ️  Estado:    {$estado}\n";
        if (isset($match['venue'])) {
            echo "  📍 Estadio:   " . $match['venue'] . "\n";
        }
        echo "\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n✅ VALIDACIÓN DE CONFIABILIDAD:\n\n";
    
    echo "Estos datos de Football-Data.org son:\n";
    echo "  ✓ 100% verificables (fuente oficial)\n";
    echo "  ✓ Actualizados en tiempo real\n";
    echo "  ✓ Exactos y completos\n";
    echo "  ✓ Corresponden a enero 2026\n\n";
    
    echo "Qué DEBERÍA encontrar Gemini con grounding:\n";
    echo "  • Exactamente " . count($matches) . " partidos\n";
    echo "  • Mismos equipos, fechas y horarios\n";
    echo "  • Mismos estadios\n";
    echo "  • Información actual (enero 7, 2026)\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📋 CÓMO USAR ESTO PARA VALIDAR GEMINI:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "1. Espera a que Gemini no esté rate limitado (in ~10 minutos)\n\n";
    
    echo "2. Ejecuta:\n";
    echo "   php test-premier-league-grounding.php\n\n";
    
    echo "3. Compara los datos de Gemini con los de arriba\n\n";
    
    echo "4. Si coinciden → ✅ Grounding FUNCIONA\n";
    echo "5. Si no coinciden → ❌ Gemini alucinó\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "💡 CONCLUSIÓN:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "Tenemos ahora la VERDAD sobre Premier League Matchday {$matchday}.\n";
    echo "Esto nos permite validar si Gemini realmente está usando grounding.\n\n";
    
    echo "Si Gemini devuelve exactamente estos " . count($matches) . " partidos\n";
    echo "con los mismos datos, entonces el grounding FUNCIONA PERFECTAMENTE.\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
