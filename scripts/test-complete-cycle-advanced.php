<?php

/**
 * SCRIPT DE PRUEBA: CICLO COMPLETO - VERSIÓN AVANZADA
 * 
 * Script mejorado con:
 * - Configuración flexible
 * - Mejor manejo de errores
 * - Validaciones más robustas
 * - Opciones de línea de comandos
 * - Más opciones de personalización
 * 
 * Uso: php scripts/test-complete-cycle-advanced.php [options]
 * Opciones:
 *   --competitions=laliga,premier    Competiciones a usar (default: laliga)
 *   --matches=5                      Número de partidos (default: 2)
 *   --users=2                        Número de usuarios de prueba (default: 1)
 *   --verbose                        Mostrar más detalles
 *   --dry-run                        Mostrar qué se haría sin hacerlo
 *   --clean                          Limpiar datos de prueba anteriores
 */

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

// ============================================================================
// CONFIGURACIÓN Y ARGUMENTOS
// ============================================================================

class TestCycleAdvanced {
    protected $config = [
        'competitions' => ['laliga'],
        'matches' => 2,
        'users' => 1,
        'verbose' => false,
        'dry_run' => false,
        'clean' => false,
        'templates' => 3,
    ];
    
    protected $stats = [
        'users_created' => 0,
        'groups_created' => 0,
        'matches_saved' => 0,
        'questions_created' => 0,
        'answers_created' => 0,
        'errors' => 0,
        'warnings' => 0,
    ];
    
    protected $colors = [];
    protected $testData = [];
    
    public function __construct() {
        $this->colors = [
            'reset' => "\033[0m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
        ];
    }
    
    public function parseArguments($argv) {
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (strpos($arg, '--') === 0) {
                $parts = explode('=', substr($arg, 2));
                $key = $parts[0];
                $value = $parts[1] ?? true;
                
                switch ($key) {
                    case 'competitions':
                        $this->config['competitions'] = explode(',', $value);
                        break;
                    case 'matches':
                        $this->config['matches'] = (int)$value;
                        break;
                    case 'users':
                        $this->config['users'] = (int)$value;
                        break;
                    case 'templates':
                        $this->config['templates'] = (int)$value;
                        break;
                    case 'verbose':
                        $this->config['verbose'] = true;
                        break;
                    case 'dry-run':
                        $this->config['dry_run'] = true;
                        break;
                    case 'clean':
                        $this->config['clean'] = true;
                        break;
                }
            }
        }
    }
    
    protected function log($level, $message) {
        $colors = [
            'section' => $this->colors['cyan'],
            'success' => $this->colors['green'],
            'error' => $this->colors['red'],
            'warning' => $this->colors['yellow'],
            'info' => $this->colors['blue'],
        ];
        
        $prefix = [
            'section' => '===',
            'success' => '✓',
            'error' => '✗',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];
        
        $color = $colors[$level] ?? $this->colors['reset'];
        $pre = $prefix[$level] ?? '';
        
        echo "{$color}{$pre} {$message}{$this->colors['reset']}\n";
        
        if ($level === 'error') {
            $this->stats['errors']++;
        } elseif ($level === 'warning') {
            $this->stats['warnings']++;
        }
    }
    
    public function run($argv) {
        $this->parseArguments($argv);
        
        $this->log('section', 'CICLO DE PRUEBA - VERSIÓN AVANZADA');
        
        if ($this->config['dry_run']) {
            $this->log('warning', 'Ejecutando en modo DRY-RUN. No se harán cambios.');
        }
        
        $this->showConfiguration();
        
        if ($this->config['clean']) {
            $this->cleanPreviousData();
        }
        
        try {
            $this->executeTestCycle();
            $this->generateReport();
        } catch (\Exception $e) {
            $this->log('error', "Error crítico: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    protected function showConfiguration() {
        $this->log('section', 'CONFIGURACIÓN');
        $this->log('info', "Competiciones: " . implode(', ', $this->config['competitions']));
        $this->log('info', "Partidos a crear: " . $this->config['matches']);
        $this->log('info', "Usuarios de prueba: " . $this->config['users']);
        $this->log('info', "Plantillas de preguntas: " . $this->config['templates']);
        
        if ($this->config['verbose']) {
            $this->log('info', "Modo verbose: SÍ");
        }
    }
    
    protected function cleanPreviousData() {
        if ($this->config['dry_run']) {
            $this->log('info', '[DRY-RUN] Se limpiarían datos de prueba anteriores');
            return;
        }
        
        $this->log('section', 'LIMPIAR DATOS ANTERIORES');
        
        try {
            $users = User::where('email', 'like', 'test-cycle-%@example.com')->get();
            $this->log('info', "Encontrados " . $users->count() . " usuarios de prueba anteriores");
            
            foreach ($users as $user) {
                $user->groups()->detach();
                $user->answers()->delete();
                $user->comments()->delete();
                $user->delete();
            }
            
            $this->log('success', "Datos anteriores limpios");
        } catch (\Exception $e) {
            $this->log('warning', "Error al limpiar: " . $e->getMessage());
        }
    }
    
    protected function executeTestCycle() {
        $this->createUsers();
        $this->obtainAndSaveMatches();
        
        foreach ($this->testData['users'] as $user) {
            auth()->setUser($user);
            $this->createGroup($user);
            $this->createQuestions();
            $this->answerQuestions($user);
        }
        
        $this->simulateResults();
        $this->verifyAndScore();
    }
    
    protected function createUsers() {
        $this->log('section', 'CREAR USUARIOS DE PRUEBA');
        
        $this->testData['users'] = [];
        
        for ($i = 0; $i < $this->config['users']; $i++) {
            $email = 'test-cycle-' . now()->timestamp . '-' . $i . '@example.com';
            
            if ($this->config['dry_run']) {
                $this->log('info', "[DRY-RUN] Se crearía usuario: {$email}");
                continue;
            }
            
            try {
                $user = User::where('email', $email)->first();
                
                if (!$user) {
                    $user = User::create([
                        'name' => 'Usuario Prueba ' . ($i + 1),
                        'email' => $email,
                        'password' => bcrypt('password123'),
                        'email_verified_at' => now(),
                    ]);
                    $this->stats['users_created']++;
                    $this->log('success', "Usuario creado: {$email}");
                } else {
                    $this->log('info', "Usuario existente: {$email}");
                }
                
                $this->testData['users'][] = $user;
            } catch (\Exception $e) {
                $this->log('error', "Error creando usuario: " . $e->getMessage());
            }
        }
    }
    
    protected function obtainAndSaveMatches() {
        $this->log('section', 'OBTENER Y GUARDAR PARTIDOS');
        
        $this->testData['matches'] = [];
        $competitions = Competition::whereIn('type', $this->config['competitions'])->get();
        
        if ($competitions->isEmpty()) {
            $this->log('error', "No se encontraron competiciones: " . implode(', ', $this->config['competitions']));
            return;
        }
        
        foreach ($competitions as $competition) {
            $this->log('info', "Procesando: {$competition->name}");
            
            $footballService = app(FootballDataService::class);
            
            $competitionMapping = [
                'champions' => 2001,
                'laliga' => 2014,
                'premier' => 2021,
            ];
            
            $competitionId = $competitionMapping[$competition->type] ?? 2014;
            
            try {
                $upcomingMatches = $footballService->getNextMatchesByCompetition($competitionId);
                $matches = array_slice($upcomingMatches, 0, $this->config['matches']);
                
                if (empty($matches)) {
                    $this->log('warning', "No hay partidos disponibles. Usando datos de prueba.");
                    $matches = $this->generateMockMatches();
                }
                
                foreach ($matches as $matchData) {
                    if ($this->config['dry_run']) {
                        $this->log('info', "[DRY-RUN] Se guardaría partido");
                        continue;
                    }
                    
                    try {
                        $homeTeam = is_array($matchData['homeTeam']) ? $matchData['homeTeam']['name'] : $matchData['homeTeam'];
                        $awayTeam = is_array($matchData['awayTeam']) ? $matchData['awayTeam']['name'] : $matchData['awayTeam'];
                        
                        $match = FootballMatch::updateOrCreate(
                            ['external_id' => $matchData['id'] ?? 'test-' . uniqid()],
                            [
                                'home_team' => $homeTeam,
                                'away_team' => $awayTeam,
                                'date' => $matchData['utcDate'] ? Carbon::parse($matchData['utcDate']) : now()->addDays(1),
                                'status' => 'Not Started',
                                'competition_id' => $competition->id,
                                'matchday' => $matchData['matchday'] ?? 1,
                                'is_featured' => true,
                                'league' => $competition->type,
                            ]
                        );
                        
                        $this->testData['matches'][] = $match;
                        $this->stats['matches_saved']++;
                        $this->log('success', "Partido: {$homeTeam} vs {$awayTeam}");
                    } catch (\Exception $e) {
                        $this->log('error', "Error guardando partido: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->log('error', "Error obteniendo partidos: " . $e->getMessage());
            }
        }
        
        if (empty($this->testData['matches'])) {
            $this->log('error', "No se guardaron partidos. Abortando.");
            throw new \Exception("No matches saved");
        }
    }
    
    protected function generateMockMatches() {
        $now = Carbon::now();
        return [
            [
                'id' => 'test-1-' . time(),
                'utcDate' => $now->addDays(1)->format('Y-m-d\T10:00:00\Z'),
                'homeTeam' => ['name' => 'Real Madrid', 'id' => 541],
                'awayTeam' => ['name' => 'Barcelona', 'id' => 529],
                'status' => 'SCHEDULED',
                'matchday' => 20,
            ],
            [
                'id' => 'test-2-' . time(),
                'utcDate' => $now->addDays(2)->format('Y-m-d\T15:00:00\Z'),
                'homeTeam' => ['name' => 'Atletico Madrid', 'id' => 530],
                'awayTeam' => ['name' => 'Sevilla', 'id' => 559],
                'status' => 'SCHEDULED',
                'matchday' => 20,
            ],
        ];
    }
    
    protected function createGroup($user) {
        $this->log('section', 'CREAR GRUPO');
        
        if ($this->config['dry_run']) {
            $this->log('info', "[DRY-RUN] Se crearía un grupo");
            return;
        }
        
        try {
            $competition = Competition::whereIn('type', $this->config['competitions'])->first();
            
            do {
                $code = \Illuminate\Support\Str::random(6);
            } while (Group::where('code', $code)->exists());
            
            $groupName = 'Grupo Prueba ' . now()->format('Y-m-d H:i:s');
            
            $group = Group::create([
                'name' => $groupName,
                'code' => $code,
                'created_by' => $user->id,
                'competition_id' => $competition->id,
                'category' => 'amateur',
            ]);
            
            $group->users()->attach($user->id);
            
            $this->testData['group'] = $group;
            $this->stats['groups_created']++;
            
            $this->log('success', "Grupo: {$groupName}");
            if ($this->config['verbose']) {
                $this->log('info', "Código: {$group->code}");
            }
        } catch (\Exception $e) {
            $this->log('error', "Error creando grupo: " . $e->getMessage());
        }
    }
    
    protected function createQuestions() {
        $this->log('section', 'CREAR PREGUNTAS PREDICTIVAS');
        
        if (!isset($this->testData['group'])) {
            $this->log('error', "No hay grupo creado");
            return;
        }
        
        $this->testData['questions'] = [];
        
        $templates = $this->getQuestionTemplates();
        $templates = array_slice($templates, 0, $this->config['templates']);
        
        foreach ($this->testData['matches'] as $match) {
            foreach ($templates as $templateData) {
                if ($this->config['dry_run']) {
                    $this->log('info', "[DRY-RUN] Se crearía una pregunta");
                    continue;
                }
                
                try {
                    $title = str_replace(
                        ['{home}', '{away}'],
                        [$match->home_team, $match->away_team],
                        $templateData['template']
                    );
                    
                    $question = Question::create([
                        'group_id' => $this->testData['group']->id,
                        'title' => $title,
                        'type' => 'predictive',
                        'available_until' => $match->date->subHours(2),
                        'category' => 'predicción',
                        'points' => 10,
                    ]);
                    
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
                            'is_correct' => $index === 0 ? true : false,
                        ]);
                    }
                    
                    $this->testData['questions'][] = $question;
                    $this->stats['questions_created']++;
                    
                    if ($this->config['verbose']) {
                        $this->log('info', "Pregunta: {$title}");
                    }
                } catch (\Exception $e) {
                    $this->log('error', "Error creando pregunta: " . $e->getMessage());
                }
            }
        }
        
        $this->log('success', "Preguntas creadas: " . count($this->testData['questions']));
    }
    
    protected function answerQuestions($user) {
        $this->log('section', 'RESPONDER PREGUNTAS');
        
        $this->testData['answers'] = [];
        
        foreach ($this->testData['questions'] ?? [] as $question) {
            if ($this->config['dry_run']) {
                $this->log('info', "[DRY-RUN] Se respondería una pregunta");
                continue;
            }
            
            try {
                $options = $question->options()->get();
                $selectedOption = $options->random();
                
                $answer = Answer::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'question_option_id' => $selectedOption->id,
                        'is_correct' => null,
                        'points_earned' => 0,
                        'category' => 'predictive',
                    ]
                );
                
                $this->testData['answers'][] = $answer;
                $this->stats['answers_created']++;
                
                if ($this->config['verbose']) {
                    $this->log('info', "Respuesta guardada: {$selectedOption->text}");
                }
            } catch (\Exception $e) {
                $this->log('error', "Error respondiendo: " . $e->getMessage());
            }
        }
        
        $this->log('success', "Respuestas guardadas: " . count($this->testData['answers']));
    }
    
    protected function simulateResults() {
        $this->log('section', 'SIMULAR RESULTADOS');
        
        foreach ($this->testData['matches'] ?? [] as $match) {
            if ($this->config['dry_run']) {
                $this->log('info', "[DRY-RUN] Se simularía un resultado");
                continue;
            }
            
            try {
                $homeScore = rand(0, 3);
                $awayScore = rand(0, 3);
                
                $winner = $homeScore > $awayScore ? 'HOME' : ($awayScore > $homeScore ? 'AWAY' : 'DRAW');
                
                $match->update([
                    'status' => 'FINISHED',
                    'home_team_score' => $homeScore,
                    'away_team_score' => $awayScore,
                    'winner' => $winner,
                ]);
                
                $this->log('success', "Resultado: {$match->home_team} {$homeScore} - {$awayScore} {$match->away_team}");
            } catch (\Exception $e) {
                $this->log('error', "Error simulando resultado: " . $e->getMessage());
            }
        }
    }
    
    protected function verifyAndScore() {
        $this->log('section', 'VERIFICAR Y ASIGNAR PUNTOS');
        
        $totalPoints = 0;
        $correctAnswers = 0;
        
        foreach ($this->testData['answers'] ?? [] as $answer) {
            if ($this->config['dry_run']) {
                $this->log('info', "[DRY-RUN] Se verificaría una respuesta");
                continue;
            }
            
            try {
                $correctOption = $answer->question->options()->where('is_correct', true)->first();
                $isCorrect = $answer->question_option_id == $correctOption->id;
                $points = $isCorrect ? 10 : 0;
                
                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_earned' => $points,
                ]);
                
                if ($isCorrect) {
                    $correctAnswers++;
                }
                $totalPoints += $points;
                
                if ($this->config['verbose']) {
                    $status = $isCorrect ? '✓' : '✗';
                    $this->log('info', "{$status} {$answer->question->title}");
                }
            } catch (\Exception $e) {
                $this->log('error', "Error verificando: " . $e->getMessage());
            }
        }
        
        $this->testData['stats'] = [
            'correct_answers' => $correctAnswers,
            'total_answers' => count($this->testData['answers'] ?? []),
            'total_points' => $totalPoints,
        ];
    }
    
    protected function generateReport() {
        $this->log('section', 'REPORTE FINAL');
        
        $this->log('info', "Usuarios creados: " . $this->stats['users_created']);
        $this->log('info', "Grupos creados: " . $this->stats['groups_created']);
        $this->log('info', "Partidos guardados: " . $this->stats['matches_saved']);
        $this->log('info', "Preguntas creadas: " . $this->stats['questions_created']);
        $this->log('info', "Respuestas creadas: " . $this->stats['answers_created']);
        
        if (isset($this->testData['stats'])) {
            $stats = $this->testData['stats'];
            $percentage = $stats['total_answers'] > 0 
                ? round(($stats['correct_answers'] / $stats['total_answers']) * 100, 2)
                : 0;
            
            $this->log('info', "");
            $this->log('info', "Respuestas correctas: " . $stats['correct_answers'] . "/" . $stats['total_answers']);
            $this->log('info', "Porcentaje de acierto: " . $percentage . "%");
            $this->log('success', "Puntos totales: " . $stats['total_points']);
        }
        
        if ($this->stats['errors'] > 0) {
            $this->log('warning', "Errores encontrados: " . $this->stats['errors']);
        }
        
        if ($this->stats['warnings'] > 0) {
            $this->log('warning', "Advertencias: " . $this->stats['warnings']);
        }
        
        $this->log('success', "Ciclo completado");
    }
    
    protected function getQuestionTemplates() {
        return [
            [
                'template' => '¿Qué equipo anotará el primer gol en {home} vs {away}?',
                'options' => ['{home}', '{away}', 'Ningún gol']
            ],
            [
                'template' => '¿Habrá más de 2.5 goles en {home} vs {away}?',
                'options' => ['Sí', 'No']
            ],
            [
                'template' => '¿Cuál será el resultado de {home} vs {away}?',
                'options' => ['Victoria {home}', 'Empate', 'Victoria {away}']
            ],
        ];
    }
}

// ============================================================================
// EJECUTAR
// ============================================================================

$testCycle = new TestCycleAdvanced();
exit($testCycle->run($argv));
