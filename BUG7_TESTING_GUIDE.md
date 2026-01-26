# Bug #7: Guía de Verificación y Testing

## ✅ Verificación Pre-Deploy

### 1. Revisar Cambios de Código

```bash
# Ver cambios en archivos
cd /c/laragon/www/offsideclub

# Verificar ProcessMatchBatchJob
grep "public \$timeout" app/Jobs/ProcessMatchBatchJob.php
# Debe mostrar: public $timeout = 300;

# Verificar BatchGetScoresJob
grep "public \$tries" app/Jobs/BatchGetScoresJob.php
# Debe mostrar: public $tries = 3;

# Verificar Kernel timing
grep "at(':15')" app/Console/Kernel.php
# Debe mostrar la línea con at(':15')

# Verificar health check job existe
ls -la app/Jobs/VerifyBatchHealthCheckJob.php
# Debe existir
```

### 2. Verificar Sintaxis PHP

```bash
# Validar sintaxis en todos los archivos modificados
php -l app/Jobs/ProcessMatchBatchJob.php
php -l app/Jobs/BatchGetScoresJob.php
php -l app/Console/Kernel.php
php -l app/Jobs/VerifyBatchHealthCheckJob.php

# Si todo OK: "No syntax errors detected"
```

### 3. Ejecutar Tests (Si Existen)

```bash
# Ejecutar tests relacionados con batch jobs
php artisan test --filter=Batch

# Si no hay tests específicos, lo haremos manualmente en deploy
```

---

## 🚀 Deploy a Producción

### 1. Hacer Backup

```bash
# Backup de BD
mysqldump offsideclub > backup_$(date +%Y%m%d_%H%M%S).sql

# Commit de cambios en git
git add app/Jobs/ProcessMatchBatchJob.php
git add app/Jobs/BatchGetScoresJob.php
git add app/Console/Kernel.php
git add app/Jobs/VerifyBatchHealthCheckJob.php
git add app/Console/Kernel.php

git commit -m "Bug #7 FIX: Aumentar timeouts, reintentos, timing gap, health check"
git push origin main
```

### 2. Deploy

```bash
# En servidor de producción
cd /path/to/offsideclub

# Pull cambios
git pull origin main

# Composer (si hay cambios en packages)
# composer install

# Cache clear
php artisan cache:clear
php artisan config:clear

# Queue workers restart
# (Si usas systemd)
sudo systemctl restart offsideclub-worker

# (O si usas supervisor)
sudo supervisorctl restart offsideclub:*
```

### 3. Verificar Queue Workers

```bash
# En servidor de producción
# Verificar que workers están corriendo
ps aux | grep "php artisan queue:work"

# Debe mostrar procesos activos

# Si no están corriendo:
# php artisan queue:work --queue=default --timeout=600 &
```

---

## 📊 Testing Manual

### Scenario 1: Verificar que Timeout fue Aumentado

```bash
# En tinker o test suite
php artisan tinker

>>> $job = new \App\Jobs\ProcessMatchBatchJob([100], 1);
>>> echo $job->timeout;  // Debe mostrar: 300
```

### Scenario 2: Verificar que Reintentos Existen

```php
>>> $job = new \App\Jobs\BatchGetScoresJob([100], 'test-batch');
>>> echo $job->tries;  // Debe mostrar: 3
```

### Scenario 3: Crear Partido de Prueba y Verificar Flujo

```bash
php artisan tinker

# Crear partido finalizado
$match = \App\Models\FootballMatch::create([
    'home_team' => 'Test Home',
    'away_team' => 'Test Away',
    'date' => now()->subHours(4),  // Hace 4 horas
    'status' => 'Not Started',      // No está finalizado
    'home_team_score' => 0,
    'away_team_score' => 0,
    'score' => '0-0',
    'competition_id' => 1,
    'league' => 'Test League'
]);

# Crear pregunta
$question = \App\Models\Question::create([
    'type' => 'predictive',
    'group_id' => 1,
    'match_id' => $match->id,
    'title' => 'Test Winner Question',
    'available_until' => $match->date->addHours(2),
    'template_question_id' => 1
]);

# Crear respuesta
$answer = \App\Models\Answer::create([
    'user_id' => 1,
    'question_id' => $question->id,
    'question_option_id' => 1  // Opción 1
]);

echo "Setup completo";
```

### Scenario 4: Simular Ejecución del Job (Manual)

```bash
php artisan tinker

# Ejecutar UpdateFinishedMatchesJob manualmente
>>> dispatch(new \App\Jobs\UpdateFinishedMatchesJob());
>>> echo "UpdateFinishedMatchesJob despachado";

# Esperar 2-3 segundos (para que ProcessMatchBatchJob se ejecute)
>>> sleep(3);

# Verificar que match fue actualizado
>>> $match->refresh();
>>> echo "Status: " . $match->status;  // Debe ser 'FINISHED' o 'Match Finished'
>>> echo "Score: " . $match->score;

# Ejecutar verificación manual
>>> dispatch(new \App\Jobs\VerifyFinishedMatchesHourlyJob());
>>> echo "VerifyFinishedMatchesHourlyJob despachado";

# Esperar 2-3 segundos
>>> sleep(3);

# Verificar que pregunta fue verificada
>>> $question->refresh();
>>> echo "Verified at: " . $question->result_verified_at;

# Verificar que respuesta tiene puntos
>>> $answer->refresh();
>>> echo "Points earned: " . $answer->points_earned;  // Debe ser > 0
```

### Scenario 5: Verificar Health Check Job

```bash
php artisan tinker

# Ejecutar health check manualmente
>>> dispatch(new \App\Jobs\VerifyBatchHealthCheckJob());
>>> echo "VerifyBatchHealthCheckJob despachado";

# Esperar 2-3 segundos
>>> sleep(3);

# Revisar logs
>>> exec('tail -20 storage/logs/laravel.log | grep -i "health\|batch\|anomal"');
```

---

## 📋 Checklist Post-Deploy (Durante Próxima Hora Programada)

### Antes de la Hora :00

- [ ] Código deployado en producción
- [ ] Queue workers reiniciados
- [ ] Logs accesibles: `tail -f storage/logs/laravel.log`
- [ ] Admin alertado de que se está monitoreando

### Durante :00

```bash
# Ver que UpdateFinishedMatchesJob se ejecuta
tail -f storage/logs/laravel.log | grep "UpdateFinishedMatchesJob"

# Debe mostrar:
# [2026-01-26 14:00:00] local.INFO: === INICIANDO: UpdateFinishedMatchesJob ===
# [2026-01-26 14:00:00] local.INFO: Partidos para actualizar: X
# [2026-01-26 14:00:XX] local.INFO: Lote 1 despachado...
```

- [ ] Se ven logs de "Lotes despachados"
- [ ] No hay "Error" o "Exception"

### Durante :10-:15

```bash
# Ver ProcessMatchBatchJob ejecutándose
tail -f storage/logs/laravel.log | grep "ProcessMatchBatchJob"

# Debe mostrar:
# [2026-01-26 14:00:10] local.INFO: Procesando lote 1 con X partidos
# [2026-01-26 14:00:XX] local.INFO: Procesando partido XXX: Team A vs Team B
```

- [ ] Se ven logos de "Procesando lote"
- [ ] Se ve "✅ Partido actualizado desde API" o "✅ Score obtenido desde Gemini"
- [ ] No hay timeouts (si hay → verificar Gemini)

### Durante :15

```bash
# Ver VerifyFinishedMatchesHourlyJob ejecutándose
tail -f storage/logs/laravel.log | grep "VerifyFinishedMatchesHourlyJob"

# Debe mostrar:
# [2026-01-26 14:15:00] local.INFO: VerifyFinishedMatchesHourlyJob started
# [2026-01-26 14:15:XX] local.INFO: VerifyFinishedMatchesHourlyJob - found candidates
```

- [ ] Se ven logs de "found candidates"
- [ ] Se ven "dispatching batch jobs"
- [ ] No hay errores

### Durante :15-:20 (Verificación de Preguntas)

```bash
# Ver VerifyAllQuestionsJob asignando puntos
tail -f storage/logs/laravel.log | grep "VerifyAllQuestionsJob"

# Debe mostrar:
# [2026-01-26 14:XX:XX] local.INFO: VerifyAllQuestionsJob started
# [2026-01-26 14:XX:XX] local.INFO: VerifyAllQuestionsJob - question verified
```

- [ ] Se ven logs de "question verified"
- [ ] Se ve "processing chunk"
- [ ] No hay "failed to verify question"

### Durante :20

```bash
# Ver VerifyBatchHealthCheckJob evaluando salud
tail -f storage/logs/laravel.log | grep "VerifyBatchHealthCheckJob"

# Debe mostrar:
# [2026-01-26 14:20:00] local.INFO: === INICIANDO: VerifyBatchHealthCheckJob ===
# [2026-01-26 14:20:XX] local.INFO: ✅ Batch verification cycle completed normally
```

- [ ] Sin alertas: "✅ Batch verification cycle completed"
- [ ] Con alertas: "⚠️ BUG #7: ANOMALÍA DETECTADA" (investigar)

---

## 🔍 Troubleshooting

### Si ves "Timeout" en logs

```
Síntoma: [2026-01-26 14:00:XX] ProcessMatchBatchJob failed - job timeout

Causa: Job superó los 300 segundos

Solución:
1. Aumentar timeout a 600 (10 min)
2. Verificar velocidad de Gemini
3. Verificar internet en servidor
```

### Si ves "Gemini error" en logs

```
Síntoma: [2026-01-26 14:XX:XX] Error al consultar Gemini: rate_limit_exceeded

Causa: Google quota agotada

Solución:
1. Esperar 1 hora (se resetea)
2. Verificar suscripción a Google API
3. Aumentar rate limit si es posible
```

### Si no ves "VerifyAllQuestionsJob" en logs

```
Síntoma: UpdateFinishedMatchesJob ✅
         ProcessMatchBatchJob ✅
         VerifyFinishedMatchesHourlyJob ✅
         VerifyAllQuestionsJob ❌ (NO APARECE)

Causa: Batch job falló en finally()

Solución:
1. Revisar logs de BatchGetScoresJob / BatchExtractEventsJob
2. Buscar líneas con "batch error"
3. Reintentar manualmente
```

### Si usuarios no tienen puntos

```
Síntoma: Usuarios ven points_earned = 0

Causa: VerifyAllQuestionsJob no asignó

Solución:
1. Verificar que respuestas existen en BD
2. Verificar que preguntas tienen result_verified_at
3. Ejecutar manualmente: dispatch(new VerifyAllQuestionsJob([match_id]));
```

---

## 📞 Escalación

Si hay issues durante deployment:

1. **Revisar logs:** `tail -100 storage/logs/laravel.log`
2. **Buscar error específico:** grep para encontrar línea de error
3. **Contactar a:** (equipo técnico)
4. **Rollback:** `git revert HEAD` si es necesario crítico

---

## ✅ Success Criteria

El deployment es **exitoso** si:

- [x] Cada hora :00 se ejecuta UpdateFinishedMatchesJob
- [x] Se ven logs de ProcessMatchBatchJob procesando partidos
- [x] Cada hora :15 se ejecuta VerifyFinishedMatchesHourlyJob
- [x] Se ven logs de VerifyAllQuestionsJob asignando puntos
- [x] Usuarios ven puntos > 0 después de las 20 minutos de cada hora
- [x] No hay "ERROR" o "timeout" en logs (o muy pocos)
- [x] VerifyBatchHealthCheckJob (:20) no muestra alertas

**Si todo está OK:** ✅ Bug #7 resuelto en producción

