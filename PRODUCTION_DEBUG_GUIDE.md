# 🔍 GUÍA DE VERIFICACIÓN Y DEBUGGING - Servidor EC2 Producción

## 📋 VERIFICACIÓN RÁPIDA

Ejecuta esto desde tu máquina local para conectar al servidor:

```bash
# Conectar al servidor
ssh ubuntu@tu-ec2-public-ip

# O si usas archivo de key:
ssh -i ruta/a/tu/key.pem ubuntu@tu-ec2-public-ip
```

---

## 1️⃣ VERIFICAR CONFIGURACIÓN DE COLA

```bash
# Ir al proyecto
cd /var/www/html/offsideclub  # Ajusta la ruta según tu servidor

# Ver la configuración actual
grep QUEUE_CONNECTION .env
# Esperado: QUEUE_CONNECTION=redis (o database)
# 🔴 MALO: QUEUE_CONNECTION=sync

# Ver si redis está ejecutándose
redis-cli ping
# Esperado: PONG
```

---

## 2️⃣ VER PROCESOS PHP Y QUEUE WORKER

```bash
# Ver si hay workers de cola ejecutándose
ps aux | grep "queue:work"
# Deberías ver algo como: php artisan queue:work redis --queue=default

# Si NO hay workers corriendo, el servidor ESTÁ BLOQUEADO
# 🟠 SOLUCIÓN: Reiniciar el worker

# Contar workers activos
ps aux | grep "queue:work" | grep -v grep | wc -l
```

---

## 3️⃣ REVISAR LOGS DE LARAVEL

```bash
# Ver últimas 50 líneas del log
tail -n 50 /var/www/html/offsideclub/storage/logs/laravel.log

# Ver en tiempo real (Ctrl+C para salir)
tail -f /var/www/html/offsideclub/storage/logs/laravel.log

# Buscar errores específicos
grep -i "ProcessRecentlyFinished\|VerifyQuestion\|CreatePredictive" \
  /var/www/html/offsideclub/storage/logs/laravel.log | tail -n 20

# Buscar todos los timeouts
grep -i "timeout\|exceeded" /var/www/html/offsideclub/storage/logs/laravel.log
```

---

## 4️⃣ REVISAR NGINX LOGS

```bash
# Ver últimas 50 líneas
tail -n 50 /var/log/nginx/error.log

# Buscar timeouts
grep -i "upstream timed out\|504\|502" /var/log/nginx/access.log | tail -n 20

# Ver accesos en tiempo real a las 3:00 AM (próxima ejecución)
tail -f /var/log/nginx/access.log
```

---

## 5️⃣ VERIFICAR ESTADO DE LA COLA

```bash
# Ir al proyecto
cd /var/www/html/offsideclub

# Ver jobs fallidos
php artisan queue:failed

# Ver jobs en la cola
php artisan queue:pending

# (Si usas Redis)
redis-cli
  > KEYS "*queue*"
  > LLEN queue:default
  > exit
```

---

## 6️⃣ VER COMANDOS PROGRAMADOS

```bash
# Ver próxima ejecución del scheduler
php artisan schedule:list

# Ejecutar scheduler ahora (para testing)
php artisan schedule:run

# Ver estado de ejecución reciente
tail -f /var/www/html/offsideclub/storage/logs/laravel.log | \
  grep -i "schedule\|ProcessRecentlyFinished"
```

---

## 7️⃣ DIAGNOSTICAR A LAS 3:00 AM (CUANDO SE EJECUTA)

**Abre 3 terminales simultáneamente a las 2:55 AM:**

### Terminal 1: Monitorear logs
```bash
ssh ubuntu@tu-ec2-public-ip
tail -f /var/www/html/offsideclub/storage/logs/laravel.log
```

### Terminal 2: Monitorear nginx
```bash
ssh ubuntu@tu-ec2-public-ip
tail -f /var/log/nginx/access.log | grep -E "503|504|timeout"
```

### Terminal 3: Monitorear procesos
```bash
ssh ubuntu@tu-ec2-public-ip
watch -n 1 "ps aux | grep -E 'php|queue|ProcessRecentlyFinished'"
# Presiona Ctrl+C para salir
```

---

## 8️⃣ VERIFICAR SUPERVISOR (Si lo usas)

```bash
# Ver estado de supervisord
sudo systemctl status supervisor

# Ver estado de workers específicos
sudo supervisorctl status

# Ver logs de supervisor
sudo tail -f /var/log/supervisor/supervisord.log

# Si un worker está en estado FATAL, reiniciarlo:
sudo supervisorctl restart laravel-worker:*
```

---

## 9️⃣ CHEQUEAR BASE DE DATOS

```bash
# Conectar a MySQL/MariaDB
mysql -u usuario -p nombre_base_datos

# Ver preguntas pendientes
SELECT COUNT(*) as pendientes FROM questions WHERE result_verified_at IS NULL;

# Ver partidos pendientes
SELECT COUNT(*) as pendientes FROM football_matches 
WHERE status NOT IN ('FINISHED', 'Match Finished');

# Ver jobs en la tabla jobs (si usas database queue)
SELECT COUNT(*) FROM jobs;
SELECT COUNT(*) FROM failed_jobs;

# Salir
exit
```

---

## 🔟 VERIFICAR ÍNDICES DE BASE DE DATOS

```bash
# Conectar a BD
mysql -u usuario -p nombre_base_datos

# Verificar si existen los índices críticos
SHOW INDEXES FROM questions;
# Buscar: idx_result_verified_at, idx_type_available

SHOW INDEXES FROM football_matches;
# Buscar: índice en status, date

SHOW INDEXES FROM answers;
# Buscar: índice en is_correct

# Crear índices si no existen:
ALTER TABLE questions ADD INDEX idx_result_verified_at (result_verified_at);
ALTER TABLE questions ADD INDEX idx_type_available (type, available_until);
ALTER TABLE answers ADD INDEX idx_is_correct (is_correct);

exit
```

---

## 1️⃣1️⃣ PRUEBAS MANUALES

```bash
cd /var/www/html/offsideclub

# Ejecutar el comando manualmente (sin esperar 3 AM)
php artisan matches:process-recently-finished

# Con output verbose
php artisan matches:process-recently-finished -v

# Medir tiempo de ejecución
time php artisan matches:process-recently-finished
```

---

## 1️⃣2️⃣ SI EL SERVIDOR SIGUE BLOQUEADO

### Opción A: Pausar el scheduler
```bash
# Editar crontab
crontab -e

# Buscar la línea con "schedule:run" y comentarla:
# * * * * * cd /var/www/html/offsideclub && php artisan schedule:run >> /dev/null 2>&1

# Salvar y salir (Ctrl+X, luego Y, Enter)
```

### Opción B: Matar procesos bloqueados
```bash
# Listar procesos PHP
ps aux | grep php

# Matar un proceso específico (usa el PID)
kill -9 <PID>

# O mata todos los workers
killall -9 php-fpm

# Reiniciar php-fpm
sudo systemctl restart php-fpm
```

### Opción C: Reiniciar todo el stack
```bash
# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php-fpm

# Queue worker (si lo tienes separado)
sudo systemctl restart queue-worker

# O si usas Supervisor:
sudo supervisorctl restart all
```

---

## 📊 SCRIPT DE DIAGNÓSTICO AUTOMÁTICO

Copia este script en tu servidor y ejecútalo:

```bash
#!/bin/bash
# guardar como: /usr/local/bin/diagnose-offside.sh
# chmod +x /usr/local/bin/diagnose-offside.sh
# Ejecutar: diagnose-offside.sh

echo "=== DIAGNÓSTICO OFFSIDE CLUB ==="
echo ""

echo "1. Verificando configuración de cola:"
grep QUEUE_CONNECTION /var/www/html/offsideclub/.env
echo ""

echo "2. Verificando workers activos:"
ps aux | grep "queue:work" | grep -v grep | wc -l
echo ""

echo "3. Últimas 10 líneas del log:"
tail -n 10 /var/www/html/offsideclub/storage/logs/laravel.log
echo ""

echo "4. Preguntas pendientes en BD:"
mysql -u usuario -p base_datos -e "SELECT COUNT(*) FROM questions WHERE result_verified_at IS NULL;"
echo ""

echo "5. Estado de Redis:"
redis-cli ping
echo ""

echo "=== FIN DIAGNÓSTICO ==="
```

---

## 🆘 CHECKLIST DE SOLUCIÓN RÁPIDA

- [ ] Verificar que `QUEUE_CONNECTION != sync` en producción
- [ ] Verificar que hay `queue:work` ejecutándose
- [ ] Ver logs de Laravel a las 3:00 AM
- [ ] Contar preguntas/partidos pendientes en BD
- [ ] Revisar si hay índices en la BD
- [ ] Verificar estado de Redis
- [ ] Revisar nginx error.log para timeouts
- [ ] Verificar supervisor/systemd status

---

## 📞 COMANDOS ÚTILES RÁPIDOS

```bash
# Estado general
systemctl status nginx php-fpm supervisor redis-server

# Reiniciar todo
systemctl restart nginx php-fpm supervisor redis-server

# Ver tráfico en tiempo real
iftop

# Ver uso de memoria
free -h

# Ver uso de CPU
top

# Ver espacio en disco
df -h

# Ver conexiones MySQL activas
mysql -u usuario -p base_datos -e "SHOW PROCESSLIST;"
```

---

## 📈 MONITOREO RECOMENDADO A FUTURO

Implementar alertas para:
- Procesos PHP ejecutándose > 5 minutos
- Queue con más de 100 jobs pendientes
- Response time de Nginx > 2 segundos
- Errores 504/502 en Nginx
- Uso de memoria > 80%
- Conexiones MySQL > 50

---
