<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;

echo "═════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO DE PREGUNTAS Y MATCHES\n";
echo "═════════════════════════════════════════════════════════════\n\n";

$matches = FootballMatch::whereIn('status', ['Finished', 'Match Finished', 'FINISHED'])
    ->where('date', '>=', now()->subDays(30))
    ->count();
echo "✅ Total matches finished (30 days): $matches\n";

$matches2 = FootballMatch::where('status', 'Finished')->count();
echo "   - status='Finished': $matches2\n";

$matches3 = FootballMatch::where('status', 'Match Finished')->count();
echo "   - status='Match Finished': $matches3\n";

$matches4 = FootballMatch::where('status', 'FINISHED')->count();
echo "   - status='FINISHED': $matches4\n\n";

$pending = Question::whereNull('result_verified_at')->count();
echo "❓ Total pending questions: $pending\n\n";

// Matches con preguntas pendientes
$matchesWithPending = FootballMatch::whereIn('status', ['Finished', 'Match Finished', 'FINISHED'])
    ->whereHas('questions', function ($query) {
        $query->whereNull('result_verified_at');
    })
    ->count();

echo "🔍 Matches finished WITH pending questions: $matchesWithPending\n";

$example = FootballMatch::where('status', 'Finished')
    ->whereHas('questions', function ($q) {
        $q->whereNull('result_verified_at');
    })
    ->with('questions')
    ->first();

if ($example) {
    echo "\n📌 Example match #$example->id:\n";
    echo "   Date: {$example->date->format('Y-m-d H:i')}\n";
    echo "   Total Questions: {$example->questions()->count()}\n";
    echo "   Pending: {$example->questions()->whereNull('result_verified_at')->count()}\n";
} else {
    echo "\n⚠️  No matches with pending questions found!\n";
}

echo "\n═════════════════════════════════════════════════════════════\n";
