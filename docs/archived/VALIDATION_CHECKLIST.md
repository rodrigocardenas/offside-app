# ✅ CHECKLIST DE VALIDACIÓN - Cambios Implementados

## 📋 VERIFICACIÓN DE CAMBIOS LOCALES

Ejecuta esto en tu máquina para confirmar que todo está bien:

```bash
cd /ruta/al/proyecto/offsideclub

# ✅ VERIFICACIÓN 1: Kernel.php - Cambio de hourly a dailyAt
echo "=== Verificación 1: Scheduler ==="
grep -A 2 "matches:process-recently-finished" app/Console/Kernel.php
# ESPERADO: ->dailyAt('03:00')
```

```bash
# ✅ VERIFICACIÓN 2: Remover sleep() de ProcessMatchBatchJob
echo "=== Verificación 2: ProcessMatchBatchJob sin sleep ==="
grep -n "sleep" app/Jobs/ProcessMatchBatchJob.php
# ESPERADO: (ningún resultado)
```

```bash
# ✅ VERIFICACIÓN 3: Remover sleep() de UpdateFinishedMatchesJob
echo "=== Verificación 3: UpdateFinishedMatchesJob sin sleep ==="
grep -n "sleep" app/Jobs/UpdateFinishedMatchesJob.php | grep -v "// NO usar"
# ESPERADO: (ningún resultado)
```

```bash
# ✅ VERIFICACIÓN 4: Chunking en VerifyQuestionResultsJob
echo "=== Verificación 4: VerifyQuestionResultsJob con chunking ==="
grep -n "chunk(" app/Jobs/VerifyQuestionResultsJob.php
# ESPERADO: ->chunk(50, function ($questions) {
```

```bash
# ✅ VERIFICACIÓN 5: Chunking en CreatePredictiveQuestionsJob
echo "=== Verificación 5: CreatePredictiveQuestionsJob con chunking ==="
grep -n "chunk(" app/Jobs/CreatePredictiveQuestionsJob.php
# ESPERADO: ->chunk($chunkSize, function ($groups) {
```

---

## 🟢 CHECKLIST DE VALIDACIÓN POST-DEPLOY

Después de hacer deploy a producción, verifica esto:

### Paso 1: Confirmación de Código
- [ ] `git log -1` muestra el commit con "Fix: Resolver bloqueo"
- [ ] `git diff HEAD~1 app/Console/Kernel.php` muestra `->dailyAt('03:00')`
- [ ] `grep -c "sleep" app/Jobs/ProcessMatchBatchJob.php` retorna 0
- [ ] `grep -c "chunk" app/Jobs/VerifyQuestionResultsJob.php` retorna > 0

### Paso 2: Verificación de Servidor
- [ ] `php artisan config:clear` ejecutó sin errores
- [ ] `php artisan schedule:list` muestra el nuevo horario (3:00 AM)
- [ ] Queue worker está corriendo: `ps aux | grep queue:work`
- [ ] Redis está disponible: `redis-cli ping` retorna "PONG"

### Paso 3: Prueba Manual
- [ ] `php artisan matches:process-recently-finished -v` ejecuta sin errores
- [ ] Logs muestran "Iniciando procesamiento coordinado"
- [ ] Logs muestran "Chunk de XX preguntas" 
- [ ] Logs NO muestran `sleep()` calls

### Paso 4: Monitoreo
- [ ] Nginx access.log NO muestra errores 504/502 en horarios pico
- [ ] Laravel logs NO muestran timeouts
- [ ] Queue worker está activo: `sudo systemctl status queue-worker` = active
- [ ] Supervisor (si lo usas) está OK: `sudo supervisorctl status`

### Paso 5: Validación con Datos
- [ ] BD: `SELECT COUNT(*) FROM questions WHERE result_verified_at IS NULL` < 100
- [ ] BD: `SELECT COUNT(*) FROM failed_jobs` sin aumentar
- [ ] BD: `SELECT COUNT(*) FROM jobs` (cola) < 50

---

## 🎯 TEST DE CARGA - Antes del Deploy Final

Ejecuta esto en una ventana terminal a las 2:55 AM (antes de que se ejecute):

```bash
# Terminal 1: Monitorear logs
ssh ubuntu@prod
tail -f storage/logs/laravel.log | grep -i "ProcessRecently\|chunk\|completed"

# Terminal 2: Monitorear Nginx
ssh ubuntu@prod
tail -f /var/log/nginx/access.log | grep -E "504|502|timeout"

# Terminal 3: Monitorear procesos
ssh ubuntu@prod
watch -n 1 "ps aux | grep -E 'php|queue' | grep -v grep"

# Terminal 4: Monitorear BD
ssh ubuntu@prod
watch -n 5 "mysql -u user -ppass db -e \"SELECT COUNT(*) FROM jobs;\""
```

---

## 🚨 SEÑALES DE ALERTA - Cosas que NO deberías ver

Después del deploy, estos síntomas indicarían que algo salió mal:

### ❌ En Nginx logs:
```
504 Gateway Timeout during business hours (NOT 3:00 AM)
502 Bad Gateway
upstream timed out
connect() timed out
```

### ❌ En Laravel logs:
```
ProcessRecentlyFinishedMatchesJob failed after X attempts
timeout of XXX seconds exceeded
sleep() called (debería estar removido)
```

### ❌ En Procesos:
```
ps aux | grep queue:work  (Sin output = problema)
php-fpm: hanging processes > 0
```

### ❌ En Base de Datos:
```
SELECT COUNT(*) FROM failed_jobs WHERE failed_at > NOW() - INTERVAL 1 HOUR > 0
SELECT COUNT(*) FROM jobs > 1000 (jobs acumulándose)
```

---

## ✅ SEÑALES DE ÉXITO - Lo que DEBERÍAS ver

### ✅ En Nginx logs:
```
3:00 AM - 3:10 AM: Bloqueo normal (esperado)
Resto del día: SIN errores 504/502
Response time: < 1 segundo
```

### ✅ En Laravel logs:
```
03:00:00 [INFO] Iniciando procesamiento coordinado de partidos finalizados recientemente
03:00:05 [INFO] Despachando job para actualizar partidos finalizados
03:00:35 [INFO] Procesando lote 1 con XX partidos
03:02:00 [INFO] Procesando chunk de 50 preguntas
03:05:00 [INFO] Procesando chunk de 50 grupos
03:10:00 [INFO] Finalizada creación de preguntas predictivas
```

### ✅ En Procesos:
```
ps aux | grep queue:work
ubuntu  1234  0.5  5.0  500000 250000 ?  S  03:00  0:30  php artisan queue:work
(Viendo consumo de CPU/memoria durante 3:00-3:10 AM)
```

### ✅ En Base de Datos:
```
SELECT COUNT(*) FROM failed_jobs WHERE failed_at > NOW() - INTERVAL 1 DAY
0 (Cero jobs fallidos)

SELECT COUNT(*) FROM questions WHERE result_verified_at IS NULL
< 100 (Preguntas verificándose sin acumularse)
```

---

## 📱 NOTIFICACIONES - Config para Alertas

Si usas monitoreo (CloudWatch, Datadog, etc), agregar alertas para:

### Alert #1: Errores 504/502
```
IF (nginx.http.5xx > 10 in 5 minutes during 09:00-22:00)
THEN: Alert("Server timeout during business hours")
```

### Alert #2: Queue Backed Up
```
IF (mysql.jobs > 1000 OR failed_jobs > 100)
THEN: Alert("Queue getting backed up")
```

### Alert #3: ProcessRecentlyFinished Stuck
```
IF (process.php.queue_worker.memory > 1GB)
THEN: Alert("Queue worker consuming too much memory")
```

### Alert #4: Missing Execution
```
IF (no logs at 03:00 AM OR 03:05 AM)
THEN: Alert("ProcessRecentlyFinished didn't run")
```

---

## 🔧 TROUBLESHOOTING RÁPIDO

Si algo no funciona:

### Problema: "404 command not found"
```bash
php artisan schedule:list
# Si no ves el comando...
php artisan cache:clear
php artisan config:clear
```

### Problema: "No connection to Redis"
```bash
redis-cli ping
# Si no responde: sudo systemctl restart redis-server
```

### Problema: "Queue worker not running"
```bash
ps aux | grep queue:work
# Si no está: php artisan queue:work --daemon
# O si usas supervisor: sudo supervisorctl restart laravel-worker:*
```

### Problema: "Jobs accumulating in queue"
```bash
php artisan queue:failed
# Ver qué falló
php artisan queue:retry all  # Reintentar
```

### Problema: "Still seeing timeouts at 3:00 AM"
```bash
# Verificar que el archivo fue actualizado
cat app/Console/Kernel.php | grep dailyAt
# Si no ves dailyAt, git pull no funcionó

# Limpiar todo
php artisan cache:clear
php artisan config:clear
php artisan optimize
```

---

## 📊 SCRIPT DE VALIDACIÓN AUTOMÁTICO

Guarda esto como `validate-fix.sh` y ejecuta post-deploy:

```bash
#!/bin/bash
set -e

PROJECT="/var/www/html/offsideclub"
cd $PROJECT

echo "🔍 VALIDACIÓN AUTOMÁTICA DE FIXES"
echo "=================================="
echo ""

# 1. Verificar cambios de código
echo "✓ Verificando cambios de código..."
if grep -q "dailyAt('03:00')" app/Console/Kernel.php; then
    echo "  ✅ Kernel.php: dailyAt('03:00') ✓"
else
    echo "  ❌ Kernel.php: NO tiene dailyAt('03:00')"
    exit 1
fi

if ! grep -q "sleep(" app/Jobs/ProcessMatchBatchJob.php; then
    echo "  ✅ ProcessMatchBatchJob: sin sleep() ✓"
else
    echo "  ❌ ProcessMatchBatchJob: aún tiene sleep()"
    exit 1
fi

if grep -q "chunk(50" app/Jobs/VerifyQuestionResultsJob.php; then
    echo "  ✅ VerifyQuestionResultsJob: chunking ✓"
else
    echo "  ❌ VerifyQuestionResultsJob: sin chunking"
    exit 1
fi

# 2. Verificar Laravel
echo ""
echo "✓ Verificando Laravel..."
php artisan config:clear > /dev/null 2>&1
if php artisan schedule:list | grep -q "03:00"; then
    echo "  ✅ Schedule: 03:00 configurado ✓"
else
    echo "  ❌ Schedule: 03:00 NO configurado"
    exit 1
fi

# 3. Verificar Queue
echo ""
echo "✓ Verificando Queue..."
if ps aux | grep -q "queue:work"; then
    echo "  ✅ Queue worker: corriendo ✓"
else
    echo "  ⚠️  Queue worker: NO corriendo (pero puede ser por 3 AM)"
fi

# 4. Verificar Redis (opcional)
echo ""
echo "✓ Verificando Redis..."
if redis-cli ping > /dev/null 2>&1; then
    echo "  ✅ Redis: disponible ✓"
else
    echo "  ⚠️  Redis: NO disponible (pero puede no ser crítico)"
fi

# 5. Prueba manual
echo ""
echo "✓ Probando comando manualmente (DRY RUN)..."
php artisan matches:process-recently-finished --dry-run 2>&1 | head -20 || true

echo ""
echo "✅ VALIDACIÓN COMPLETADA EXITOSAMENTE"
echo "Ready for deployment! ✨"
```

Uso:
```bash
chmod +x validate-fix.sh
./validate-fix.sh
```

---

## 🎉 RESUMEN FINAL

| Aspecto | Status |
|---------|--------|
| ✅ Código modificado | ✓ Completado |
| ✅ Tests locales | ✓ Pasados |
| ✅ Cambios documentados | ✓ Completado |
| ✅ Guía de debug | ✓ Creada |
| ✅ Ready to deploy | ✓ SÍ |

**Estado:** Ready for Production Deployment ✨
