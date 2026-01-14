-- Verificar partidos con datos ficticios
SELECT
    id,
    home_team,
    away_team,
    score,
    status,
    events,
    statistics
FROM football_matches
WHERE events LIKE '%Fallback%'
   OR events LIKE '%random%'
   OR events LIKE '%4 goles del local, 1 del visitante%'
ORDER BY updated_at DESC
LIMIT 20;
