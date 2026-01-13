<?php

// Script de diagnóstico para verificar por qué no se crean preguntas

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Group;
use App\Models\FootballMatch;
use App\Models\TemplateQuestion;
use App\Models\Question;

// Obtener el último grupo creado
$lastGroup = Group::orderBy('id', 'desc')->first();

if (!$lastGroup) {
    echo "❌ No hay grupos en la BD\n";
    exit(1);
}

echo "📋 DIAGNÓSTICO: Grupo #{$lastGroup->id} - {$lastGroup->name}\n";
echo "================================================\n\n";

// 1. Verificar partidos próximos globales
echo "1️⃣  Partidos próximos (global):\n";
$allMatches = FootballMatch::where('status', 'Not Started')
    ->where('date', '>=', now())
    ->orderBy('date')
    ->take(10)
    ->get();

if ($allMatches->isEmpty()) {
    echo "   ❌ NO hay partidos próximos en la BD\n";
} else {
    echo "   ✅ Partidos encontrados: " . $allMatches->count() . "\n";
    $allMatches->each(function($m) {
        echo "   - {$m->home_team} vs {$m->away_team} el {$m->date}\n";
    });
}
echo "\n";

// 2. Verificar templates predictivos
echo "2️⃣  Templates predictivos:\n";
$templates = TemplateQuestion::where('type', 'predictive')->get();

if ($templates->isEmpty()) {
    echo "   ❌ NO hay templates predictivos en la BD\n";
} else {
    echo "   ✅ Templates encontrados: " . $templates->count() . "\n";
    $templates->take(3)->each(function($t) {
        echo "   - " . substr($t->text, 0, 60) . "...\n";
    });
}
echo "\n";

// 3. Verificar preguntas actuales del grupo
echo "3️⃣  Preguntas actuales del grupo:\n";
$currentQuestions = Question::where('type', 'predictive')
    ->where('group_id', $lastGroup->id)
    ->where('available_until', '>', now())
    ->get();

if ($currentQuestions->isEmpty()) {
    echo "   ❌ NO hay preguntas vigentes\n";
} else {
    echo "   ✅ Preguntas vigentes: " . $currentQuestions->count() . "\n";
}
echo "\n";

// 4. Verificar si hay competición
echo "4️⃣  Competición del grupo:\n";
if (!$lastGroup->competition_id) {
    echo "   ⚠️  El grupo NO tiene competición asignada\n";
} else {
    echo "   ✅ Competición: {$lastGroup->competition->type}\n";
}
echo "\n";

// 5. Intentar crear las preguntas manualmente
echo "5️⃣  Intentando crear preguntas:\n";
try {
    // Simular lo que hace el trait
    $vigentes = Question::where('type', 'predictive')
        ->where('group_id', $lastGroup->id)
        ->where('available_until', '>', now())
        ->get();

    $faltantes = 5 - $vigentes->count();
    echo "   - Preguntas vigentes: {$vigentes->count()}\n";
    echo "   - Faltantes para llegar a 5: {$faltantes}\n";

    if ($faltantes <= 0) {
        echo "   ⚠️  Ya hay 5 o más preguntas, no se crean más\n";
    } else {
        $matches = FootballMatch::where('status', 'Not Started')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->get();

        echo "   - Partidos próximos totales: {$matches->count()}\n";

        $matchesSinPregunta = $matches->filter(function($match) use ($lastGroup) {
            return !Question::where('type', 'predictive')
                ->where('group_id', $lastGroup->id)
                ->where('match_id', $match->id)
                ->where('available_until', '>', now())
                ->exists();
        });

        echo "   - Partidos sin pregunta vigente: {$matchesSinPregunta->count()}\n";

        if ($matchesSinPregunta->isEmpty()) {
            echo "   ⚠️  Todos los partidos ya tienen preguntas en este grupo\n";
        } else {
            echo "   ✅ Se pueden crear preguntas para {$matchesSinPregunta->count()} partidos\n";
        }

        // Verificar templates
        $plantillas = TemplateQuestion::where('type', 'predictive')->get();
        echo "   - Templates disponibles: {$plantillas->count()}\n";

        if ($plantillas->isEmpty()) {
            echo "   ❌ NO hay templates para crear preguntas\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

echo "\n================================================\n";
echo "🔍 RESUMEN:\n";
echo "- Partidos próximos: " . ($allMatches->count() > 0 ? "✅" : "❌") . "\n";
echo "- Templates: " . ($templates->count() > 0 ? "✅" : "❌") . "\n";
echo "- Preguntas vigentes: {$currentQuestions->count()}\n";
echo "- Competición: " . ($lastGroup->competition_id ? "✅" : "❌") . "\n";
