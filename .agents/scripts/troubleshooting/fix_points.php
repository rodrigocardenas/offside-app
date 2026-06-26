<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MatchModel;
use App\Models\Question;
use App\Models\GroupUser;
use Illuminate\Support\Facades\DB;
use App\Jobs\VerifyQuestionResultsJob;

echo "--- Verificando Qatar vs Suiza y EEUU vs Paraguay en grupo 103 ---\n";

$matches = MatchModel::whereIn('home_team', ['Qatar', 'Estados Unidos', 'USA'])
    ->orWhereIn('away_team', ['Suiza', 'Paraguay'])
    ->get();

foreach ($matches as $match) {
    if ($match->status !== 'FINISHED') {
        echo "Match {$match->home_team} vs {$match->away_team} no esta en FINISHED (esta {$match->status}). Lo actualizo a FINISHED.\n";
        $match->status = 'FINISHED';
        $match->save();
    }
    
    $questions = Question::where('match_id', $match->id)->get();
    foreach ($questions as $q) {
        echo "Verificando pregunta: {$q->question_text} (ID: {$q->id})\n";
        // Despachar el job localmente
        VerifyQuestionResultsJob::dispatchSync($q);
    }
}

echo "--- Sincronizando puntos de todos los usuarios (UpdateGroupTotalPointsJob) ---\n";

$groups = DB::table('groups')->pluck('id');
foreach ($groups as $groupId) {
    // La logica de UpdateGroupTotalPointsJob para cada grupo
    $users = GroupUser::where('group_id', $groupId)->get();
    foreach ($users as $gu) {
        $totalPoints = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('questions.group_id', $groupId)
            ->where('answers.user_id', $gu->user_id)
            ->sum('answers.points');
        
        $gu->points = $totalPoints;
        $gu->save();
    }
}

echo "Puntos sincronizados!\n";
