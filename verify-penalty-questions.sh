#!/bin/bash
# Script para verificar que las preguntas de penales se verifican correctamente con fallback Gemini

echo "=========================================="
echo "Verificación de Preguntas de Penales"
echo "=========================================="
echo ""

# 1. Buscar matches con preguntas de penales
echo "[1] Buscando matches con preguntas de penales..."
mysql -u homestead -ppassword offside2 <<EOF
SELECT DISTINCT m.id, m.home_team, m.away_team, m.date, COUNT(q.id) as penalty_questions
FROM football_matches m
INNER JOIN questions q ON m.id = q.football_match_id
WHERE q.title LIKE '%penal%' OR q.title LIKE '%penalty%'
  AND m.date >= DATE_SUB(NOW(), INTERVAL 15 DAY)
GROUP BY m.id
LIMIT 5;
EOF

echo ""
echo "[2] Ejecutando verificación de preguntas de penales para match 297..."
cd /laragon/www/offsideclub
php artisan app:force-verify-questions --match-id=297 --limit=10

echo ""
echo "[3] Buscando logs de fallback Gemini en los últimos 5 minutos..."
grep -A3 "Gemini fallback attempting\|penalty information NOT found" storage/logs/laravel.log | tail -50

echo ""
echo "[4] Contando preguntas de penales verificadas..."
mysql -u homestead -ppassword offside2 <<EOF
SELECT COUNT(DISTINCT a.id) as verified_penalty_answers,
       COUNT(DISTINCT a.question_id) as unique_penalty_questions,
       SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
FROM answers a
INNER JOIN questions q ON a.question_id = q.id
WHERE (q.title LIKE '%penal%' OR q.title LIKE '%penalty%')
  AND a.result_verified_at IS NOT NULL
  AND q.football_match_id >= (
    SELECT MAX(id) - 100 FROM football_matches
  )
LIMIT 1;
EOF

echo ""
echo "=========================================="
echo "Verificación completada"
echo "=========================================="
