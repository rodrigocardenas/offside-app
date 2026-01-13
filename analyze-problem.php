<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FootballMatch;
use Carbon\Carbon;

echo "🔍 ANÁLISIS DETALLADO DEL PROBLEMA\n";
echo "===================================\n\n";

// Hora actual del sistema
$now = now();
echo "Hora actual del sistema: {$now}\n";
echo "Fecha actual (sin hora): " . $now->toDateString() . "\n\n";

// Partidos en la BD
echo "Partidos en la BD:\n";
$matches = FootballMatch::orderBy('date')->take(10)->get();
$matches->each(function($m) use ($now) {
    $matchDate = $m->date;
    $isFuture = $matchDate >= $now;
    $status = $isFuture ? "✅ FUTURO" : "❌ PASADO";
    echo "  • {$matchDate} {$status}\n";
});

// El gran problema
echo "\n🚨 ANÁLISIS:\n";
$futureMatches = FootballMatch::where('date', '>=', $now)->get();
echo "Partidos con date >= now(): " . $futureMatches->count() . "\n";

if ($futureMatches->isEmpty()) {
    echo "\n❌ CAUSA RAÍZ:\n";
    echo "   Todos los partidos en la BD están en el PASADO.\n";
    echo "   El sistema busca: date >= now()\n";
    echo "   Pero now() = {$now}\n";
    echo "   Y los partidos más recientes son del 2026-01-13\n\n";

    echo "🔧 SOLUCIONES:\n";
    echo "\n1️⃣  OPCIÓN A - Importar partidos futuros:\n";
    echo "   php artisan gemini:fetch-fixtures premier --force\n";
    echo "   php artisan gemini:fetch-fixtures laliga --force\n";
    echo "   php artisan gemini:fetch-fixtures champions --force\n\n";

    echo "2️⃣  OPCIÓN B - Crear partidos de prueba futuros:\n";
    echo "   Necesitas un seeder que cree partidos con fechas futuras\n\n";

    echo "3️⃣  OPCIÓN C - Usar el comando existente:\n";
    echo "   php artisan app:update-fixtures-nightly\n\n";
}

// Verificar si hay un problema con la zona horaria
echo "\n⏰ VERIFICACIÓN DE ZONA HORARIA:\n";
echo "Timezone del sistema: " . config('app.timezone') . "\n";
echo "Hora actual en UTC: " . now()->setTimezone('UTC')->format('Y-m-d H:i:s') . "\n";
echo "Hora actual (local): " . now()->format('Y-m-d H:i:s') . "\n";
