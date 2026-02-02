<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Question;

// Las preguntas que mencionaste
$questionIds = [288, 300, 320, 322, 308];

echo "═════════════════════════════════════════════════════════════\n";
echo "ESTADO DE LAS PREGUNTAS DEL CASO\n";
echo "═════════════════════════════════════════════════════════════\n\n";

foreach ($questionIds as $id) {
    $question = Question::with(['football_match', 'options'])->find($id);

    if (!$question) {
        echo "❌ Pregunta #$id no existe\n\n";
        continue;
    }

    echo "📌 Pregunta #$id\n";
    echo "   Texto: " . substr($question->text, 0, 60) . "...\n";
    echo "   Match: #{$question->football_match->id}\n";
    echo "   Status match: {$question->football_match->status}\n";
    echo "   Verificada: " . ($question->result_verified_at ? "✅ " . $question->result_verified_at : "❌ NO") . "\n";

    if ($question->result_verified_at) {
        echo "   Resultado: {$question->result} \n";
        $correct = $question->options()->where('is_correct', 1)->first();
        if ($correct) {
            echo "   Opción correcta: {$correct->text}\n";
        }
    }

    echo "\n";
}

echo "═════════════════════════════════════════════════════════════\n";
