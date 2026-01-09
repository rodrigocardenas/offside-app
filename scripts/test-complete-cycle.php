<?php

/**
 * SCRIPT DE PRUEBA: CICLO COMPLETO DE LA APLICACIÓN
 * 
 * Este script realiza un ciclo completo de la aplicación:
 * 1. Obtiene partidos próximos de las APIs (datos reales)
 * 2. Los guarda en la base de datos
 * 3. Crea un grupo
 * 4. Genera preguntas predictivas para ese grupo
 * 5. Responde las preguntas con un usuario de prueba
 * 6. Obtiene los resultados de los partidos
 * 7. Verifica las respuestas y asigna puntos
 * 8. Genera un reporte del ciclo
 * 
 * Uso: php scripts/test-complete-cycle.php
 */

// Cargar el autoloader de Composer y configurar Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Group;
use App\Models\Competition;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use App\Services\FootballDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// Colores para la salida en terminal
class Colors {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
}

function print_section($title) {
    echo "\n" . Colors::CYAN . "=== " . $title . " ===" . Colors::RESET . "\n";
}

function print_success($message) {
    echo Colors::GREEN . "✓ " . $message . Colors::RESET . "\n";
}

function print_error($message) {
    echo Colors::RED . "✗ " . $message . Colors::RESET . "\n";
}

function print_info($message) {
    echo Colors::BLUE . "ℹ " . $message . Colors::RESET . "\n";
}

function print_warning($message) {
    echo Colors::YELLOW . "⚠ " . $message . Colors::RESET . "\n";
}

// ============================================================================
// 1. OBTENER O CREAR USUARIO DE PRUEBA
// ============================================================================
print_section("PASO 1: Obtener o crear usuario de prueba");

$testEmail = 'test-cycle-' . now()->timestamp . '@example.com';
$testUser = User::where('email', $testEmail)->first();

if (!$testUser) {
    $testUser = User::create([
        'name' => 'Usuario Prueba Ciclo',
        'email' => $testEmail,
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);
    print_success("Usuario creado: {$testUser->email}");
} else {
    print_success("Usuario existente: {$testUser->email}");
}

// Autenticar como el usuario de prueba para las operaciones
auth()->setUser($testUser);

// ============================================================================
// 2. OBTENER COMPETICIONES DISPONIBLES
// ============================================================================
print_section("PASO 2: Obtener competiciones disponibles");

$competitions = Competition::whereIn('type', ['laliga', 'premier', 'champions'])->get();

if ($competitions->isEmpty()) {
    print_error("No hay competiciones disponibles en la BD");
    exit(1);
}

print_success("Competiciones encontradas: " . $competitions->count());
$competitions->each(function($comp) {
    print_info("- {$comp->name} ({$comp->type})");
});

$selectedCompetition = $competitions->first();
print_success("Competición seleccionada: {$selectedCompetition->name}");

// ============================================================================
// 3. OBTENER PARTIDOS PRÓXIMOS DE LA API
// ============================================================================
print_section("PASO 3: Obtener partidos próximos de la API");

$footballService = app(FootballDataService::class);
$competitionMapping = [
    'champions' => 2001,  // UEFA Champions League
    'laliga' => 2014,     // La Liga
    'premier' => 2021,    // Premier League
];

$competitionId = $competitionMapping[$selectedCompetition->type] ?? 2014;
print_info("Obteniendo partidos para competición: {$selectedCompetition->type} (ID: {$competitionId})");

try {
    $upcomingMatches = $footballService->getNextMatchesByCompetition($competitionId);
    
    if (empty($upcomingMatches)) {
        print_warning("No hay partidos próximos disponibles en la API");
        print_info("Usando datos de prueba...");
        $upcomingMatches = [
            [
                'id' => 'test-1-' . time(),
                'utcDate' => now()->addDays(1)->format('Y-m-d\T10:00:00\Z'),
                'homeTeam' => ['name' => 'Real Madrid', 'id' => 541],
                'awayTeam' => ['name' => 'Barcelona', 'id' => 529],
                'status' => 'SCHEDULED',
                'matchday' => 20,
            ],
            [
                'id' => 'test-2-' . time(),
                'utcDate' => now()->addDays(2)->format('Y-m-d\T15:00:00\Z'),
                'homeTeam' => ['name' => 'Atletico Madrid', 'id' => 530],
                'awayTeam' => ['name' => 'Sevilla', 'id' => 559],
                'status' => 'SCHEDULED',
                'matchday' => 20,
            ],
        ];
    }
    
    print_success("Se obtuvieron " . count($upcomingMatches) . " partidos próximos");
    foreach ($upcomingMatches as $match) {
        $homeTeam = is_array($match['homeTeam']) ? $match['homeTeam']['name'] : $match['homeTeam'];
        $awayTeam = is_array($match['awayTeam']) ? $match['awayTeam']['name'] : $match['awayTeam'];
        $date = $match['utcDate'] ?? 'N/A';
        print_info("- {$homeTeam} vs {$awayTeam} ({$date})");
    }
} catch (\Exception $e) {
    print_error("Error obteniendo partidos: " . $e->getMessage());
    exit(1);
}

// ============================================================================
// 4. GUARDAR PARTIDOS EN BD
// ============================================================================
print_section("PASO 4: Guardar partidos en BD");

$savedMatches = [];
foreach (array_slice($upcomingMatches, 0, 2) as $matchData) {
    try {
        $homeTeamName = is_array($matchData['homeTeam']) ? $matchData['homeTeam']['name'] : $matchData['homeTeam'];
        $awayTeamName = is_array($matchData['awayTeam']) ? $matchData['awayTeam']['name'] : $matchData['awayTeam'];
        $externalId = $matchData['id'] ?? 'test-' . uniqid();
        
        $match = FootballMatch::updateOrCreate(
            ['external_id' => $externalId],
            [
                'home_team' => $homeTeamName,
                'away_team' => $awayTeamName,
                'date' => $matchData['utcDate'] ? Carbon::parse($matchData['utcDate']) : now()->addDays(1),
                'status' => 'Not Started',
                'competition_id' => $selectedCompetition->id,
                'matchday' => $matchData['matchday'] ?? 1,
                'is_featured' => true,
                'league' => $selectedCompetition->type,
            ]
        );
        
        $savedMatches[] = $match;
        print_success("Partido guardado: {$homeTeamName} vs {$awayTeamName}");
    } catch (\Exception $e) {
        print_error("Error guardando partido: " . $e->getMessage());
    }
}

if (empty($savedMatches)) {
    print_error("No se guardaron partidos en la BD");
    exit(1);
}

// ============================================================================
// 5. CREAR UN GRUPO
// ============================================================================
print_section("PASO 5: Crear un grupo");

try {
    $groupName = 'Grupo Prueba ' . now()->format('Y-m-d H:i:s');
    
    // Generar código único
    do {
        $code = \Illuminate\Support\Str::random(6);
    } while (Group::where('code', $code)->exists());
    
    $group = Group::create([
        'name' => $groupName,
        'code' => $code,
        'created_by' => $testUser->id,
        'competition_id' => $selectedCompetition->id,
        'category' => 'amateur',
    ]);
    
    // Añadir usuario al grupo
    $group->users()->attach($testUser->id);
    
    print_success("Grupo creado: {$group->name}");
    print_info("Código del grupo: {$group->code}");
} catch (\Exception $e) {
    print_error("Error creando grupo: " . $e->getMessage());
    exit(1);
}

// ============================================================================
// 6. GENERAR PREGUNTAS PREDICTIVAS
// ============================================================================
print_section("PASO 6: Generar preguntas predictivas");

$templates = [
    [
        'template' => '¿Qué equipo anotará el primer gol en el partido {home} vs {away}?',
        'options' => ['{home}', '{away}', 'Ningún equipo (0-0)']
    ],
    [
        'template' => '¿Habrá más de 2.5 goles en el partido {home} vs {away}?',
        'options' => ['Sí', 'No']
    ],
    [
        'template' => '¿Cuál será el resultado del partido {home} vs {away}?',
        'options' => ['Victoria {home}', 'Empate', 'Victoria {away}']
    ],
];

$questions = [];
foreach ($savedMatches as $match) {
    foreach ($templates as $templateData) {
        try {
            $template = $templateData['template'];
            $title = str_replace(
                ['{home}', '{away}'],
                [$match->home_team, $match->away_team],
                $template
            );
            
            $question = Question::create([
                'group_id' => $group->id,
                'title' => $title,
                'type' => 'predictive',
                'available_until' => $match->date->subHours(2), // Disponible hasta 2 horas antes
                'category' => 'predicción',
                'points' => 10,
            ]);
            
            // Crear opciones
            $optionsText = array_map(function($opt) use ($match) {
                return str_replace(
                    ['{home}', '{away}'],
                    [$match->home_team, $match->away_team],
                    $opt
                );
            }, $templateData['options']);
            
            foreach ($optionsText as $index => $optionText) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'text' => $optionText,
                    'is_correct' => $index === 0 ? true : false, // Por ahora, la primera opción es correcta
                ]);
            }
            
            $questions[] = $question;
            print_success("Pregunta creada: {$title}");
        } catch (\Exception $e) {
            print_error("Error creando pregunta: " . $e->getMessage());
        }
    }
}

if (empty($questions)) {
    print_error("No se crearon preguntas");
    exit(1);
}

print_success("Total de preguntas creadas: " . count($questions));

// ============================================================================
// 7. RESPONDER LAS PREGUNTAS
// ============================================================================
print_section("PASO 7: Responder las preguntas con el usuario de prueba");

$answers = [];
foreach ($questions as $question) {
    try {
        // Seleccionar una opción al azar
        $options = $question->options()->get();
        $selectedOption = $options->random();
        
        $answer = Answer::updateOrCreate(
            [
                'user_id' => $testUser->id,
                'question_id' => $question->id,
            ],
            [
                'question_option_id' => $selectedOption->id,
                'is_correct' => null,
                'points_earned' => 0,
                'category' => 'predictive',
            ]
        );
        
        $answers[] = $answer;
        print_success("Respuesta guardada para pregunta: {$question->title}");
        print_info("  Opción seleccionada: {$selectedOption->text}");
    } catch (\Exception $e) {
        print_error("Error respondiendo pregunta: " . $e->getMessage());
    }
}

print_success("Total de respuestas: " . count($answers));

// ============================================================================
// 8. SIMULAR RESULTADOS DE PARTIDOS
// ============================================================================
print_section("PASO 8: Simular resultados de partidos");

$results = [];
foreach ($savedMatches as $match) {
    try {
        $homeScore = rand(0, 3);
        $awayScore = rand(0, 3);
        
        // Determinar ganador
        if ($homeScore > $awayScore) {
            $winner = 'HOME';
        } elseif ($awayScore > $homeScore) {
            $winner = 'AWAY';
        } else {
            $winner = 'DRAW';
        }
        
        $match->update([
            'status' => 'FINISHED',
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'winner' => $winner,
        ]);
        
        $results[] = [
            'match' => $match,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'winner' => $winner,
        ];
        
        print_success("Resultado guardado: {$match->home_team} {$homeScore} - {$awayScore} {$match->away_team}");
    } catch (\Exception $e) {
        print_error("Error guardando resultado: " . $e->getMessage());
    }
}

// ============================================================================
// 9. VERIFICAR RESPUESTAS Y ASIGNAR PUNTOS
// ============================================================================
print_section("PASO 9: Verificar respuestas y asignar puntos");

$totalPoints = 0;
foreach ($questions as $question) {
    try {
        // Obtener la respuesta del usuario
        $userAnswer = $question->answers()->where('user_id', $testUser->id)->first();
        
        if (!$userAnswer) {
            print_warning("No hay respuesta para: {$question->title}");
            continue;
        }
        
        // Verificar si la respuesta es correcta
        $correctOption = $question->options()->where('is_correct', true)->first();
        $isCorrect = $userAnswer->question_option_id == $correctOption->id;
        
        // Asignar puntos
        $pointsEarned = $isCorrect ? 10 : 0;
        
        $userAnswer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
        ]);
        
        $totalPoints += $pointsEarned;
        
        $status = $isCorrect ? '✓ CORRECTA' : '✗ INCORRECTA';
        print_info("{$status}: {$question->title}");
        print_info("  Respuesta del usuario: {$userAnswer->questionOption->text}");
        print_info("  Respuesta correcta: {$correctOption->text}");
        print_info("  Puntos ganados: {$pointsEarned}");
    } catch (\Exception $e) {
        print_error("Error verificando respuesta: " . $e->getMessage());
    }
}

// ============================================================================
// 10. GENERAR REPORTE FINAL
// ============================================================================
print_section("PASO 10: Reporte final del ciclo");

$finalAnswers = Answer::where('user_id', $testUser->id)
    ->whereHas('question', function($q) use ($group) {
        $q->where('group_id', $group->id);
    })
    ->get();

$correctAnswers = $finalAnswers->where('is_correct', true)->count();
$totalAnswers = $finalAnswers->count();
$totalPointsVerify = $finalAnswers->sum('points_earned');

print_info("Usuario: {$testUser->name} ({$testUser->email})");
print_info("Grupo: {$group->name}");
print_info("Competición: {$selectedCompetition->name}");
print_info("Partidos: " . count($savedMatches));
print_info("Preguntas creadas: " . count($questions));
print_info("Respuestas: {$totalAnswers}");
print_info("Respuestas correctas: {$correctAnswers}");
print_info("Porcentaje de acierto: " . ($totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0) . "%");
print_success("Puntos totales: {$totalPointsVerify}");

// ============================================================================
// 11. GUARDAR INFORMACIÓN EN ARCHIVO LOG
// ============================================================================
print_section("PASO 11: Guardar información en archivo log");

$logFile = storage_path('logs/test-cycle-' . now()->format('Y-m-d-H-i-s') . '.txt');
$logContent = "REPORTE DEL CICLO COMPLETO DE LA APLICACIÓN\n";
$logContent .= "==========================================\n\n";
$logContent .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n";
$logContent .= "Usuario: {$testUser->name} ({$testUser->email})\n";
$logContent .= "Grupo: {$group->name} (Código: {$group->code})\n";
$logContent .= "Competición: {$selectedCompetition->name}\n\n";

$logContent .= "PARTIDOS GUARDADOS:\n";
$logContent .= str_repeat("-", 50) . "\n";
foreach ($savedMatches as $match) {
    $logContent .= "{$match->home_team} vs {$match->away_team}\n";
    $logContent .= "Fecha: {$match->date}\n";
    $logContent .= "Resultado: {$match->home_team_score} - {$match->away_team_score}\n";
    $logContent .= "Ganador: {$match->winner}\n\n";
}

$logContent .= "PREGUNTAS Y RESPUESTAS:\n";
$logContent .= str_repeat("-", 50) . "\n";
foreach ($finalAnswers as $answer) {
    $status = $answer->is_correct ? '✓' : '✗';
    $logContent .= "[{$status}] {$answer->question->title}\n";
    $logContent .= "Respuesta: {$answer->questionOption->text}\n";
    $logContent .= "Puntos: {$answer->points_earned}\n\n";
}

$logContent .= "RESUMEN:\n";
$logContent .= str_repeat("-", 50) . "\n";
$logContent .= "Preguntas: {$totalAnswers}\n";
$logContent .= "Respuestas correctas: {$correctAnswers}\n";
$logContent .= "Porcentaje: " . ($totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0) . "%\n";
$logContent .= "Puntos totales: {$totalPointsVerify}\n";

file_put_contents($logFile, $logContent);
print_success("Reporte guardado en: {$logFile}");

// ============================================================================
// RESUMEN FINAL
// ============================================================================
print_section("CICLO COMPLETO FINALIZADO");
print_success("El ciclo completo de la aplicación se ha ejecutado exitosamente");
print_info("Revisa el archivo de log para más detalles");
print_info("Acceso a la aplicación: http://localhost/offsideclub");
print_info("Email del usuario de prueba: {$testUser->email}");
print_info("Contraseña: password123");

echo "\n";
