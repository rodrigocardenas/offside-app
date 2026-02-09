# ⚙️ Cómo Ejecutar el Sistema en Producción

## 🎯 Objetivo
El sistema automáticamente actualizará los resultados de partidos cada hora usando API Football PRO.

---

## 🚀 Ejecución Inmediata (Desarrollo)

### 1. Verificar que todo esté configurado
```bash
# Verificar configuración de .env
grep "FOOTBALL_API_KEY" .env

# Verificar que API Football está conectada
php test-api-pro.php
```

### 2. Ejecutar el Queue Worker
```bash
# Terminal 1: Ejecutar queue worker (se ejecutará infinitamente)
php artisan queue:work

# Output esperado:
# INFO  Processing jobs from the [default] queue.
# ...
# (esperará jobs)
```

### 3. Verificar logs en tiempo real
```bash
# Terminal 2: Ver logs en vivo
tail -f storage/logs/laravel.log | grep -E "INICIANDO|ACTUALIZADO|ERROR"
```

### 4. Disparar jobs manualmente (para testing)
```bash
# Terminal 3: Enviar un job a la queue
php artisan tinker
> dispatch(new \App\Jobs\UpdateFinishedMatchesJob);
```

---

## 📋 Pipeline Automático (Scheduler)

### ¿Cómo funciona?
Laravel cron se ejecuta cada minuto:
```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Configuración en `app/Console/Kernel.php`
```php
protected function schedule(Schedule $schedule)
{
    // Actualizar partidos cada hora a los :00
    $schedule->job(new UpdateFinishedMatchesJob)
        ->hourly()
        ->at('00');
    
    // Verificar partidos actualizados cada hora a los :05
    $schedule->job(new VerifyFinishedMatchesHourlyJob)
        ->hourly()
        ->at('05');
}
```

### Ciclo de ejecución
```
00:00 → UpdateFinishedMatchesJob (busca resultados en API Football)
↓
00:01-00:04 → ProcessMatchBatchJob (procesa lotes)
↓
00:05 → VerifyFinishedMatchesHourlyJob (verifica eventos)
↓
...
01:00 → Repite el ciclo
```

---

## 🏠 Configuración para Producción

### Opción 1: Systemd (Linux)

#### Crear archivo: `/etc/systemd/system/offside-queue.service`
```ini
[Unit]
Description=Offside Club Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/offsideclub
ExecStart=/usr/bin/php /var/www/offsideclub/artisan queue:work --tries=1
Restart=always
RestartSec=10
StandardOutput=append:/var/www/offsideclub/storage/logs/queue-worker.log
StandardError=append:/var/www/offsideclub/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
```

#### Iniciar servicio
```bash
sudo systemctl daemon-reload
sudo systemctl start offside-queue
sudo systemctl enable offside-queue  # Auto-start on boot

# Ver estado
sudo systemctl status offside-queue

# Ver logs
sudo tail -f /var/www/offsideclub/storage/logs/queue-worker.log
```

### Opción 2: PM2 (Node.js)

#### Instalar PM2
```bash
npm install pm2 -g
```

#### Crear archivo: `ecosystem.config.js`
```javascript
module.exports = {
  apps: [
    {
      name: 'offside-queue',
      script: 'artisan',
      args: 'queue:work --tries=1',
      instances: 1,
      exec_mode: 'cluster',
      watch: false,
      error_file: './storage/logs/pm2-error.log',
      out_file: './storage/logs/pm2-out.log',
      env: {
        NODE_ENV: 'production'
      }
    }
  ]
};
```

#### Iniciar con PM2
```bash
pm2 start ecosystem.config.js
pm2 save
pm2 startup

# Ver estado
pm2 status

# Ver logs
pm2 logs offside-queue
```

### Opción 3: Supervisor (Recomendado para servidores compartidos)

#### Instalar Supervisor
```bash
sudo apt-get install supervisor
```

#### Crear archivo: `/etc/supervisor/conf.d/offside-queue.conf`
```ini
[program:offside-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/offsideclub/artisan queue:work --tries=1
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/offsideclub/storage/logs/supervisor.log
```

#### Iniciar Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start offside-queue:*

# Ver estado
sudo supervisorctl status offside-queue:*

# Ver logs
tail -f /var/www/offsideclub/storage/logs/supervisor.log
```

---

## 🔍 Monitoreo

### Ver trabajos en la queue
```bash
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all
```

### Ver histórico de jobs
```sql
-- Base de datos (si usa database driver)
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;
```

### Ver partidos actualizados hoy
```sql
SELECT 
    id, 
    home_team, 
    away_team, 
    home_team_score,
    away_team_score,
    status,
    updated_at
FROM football_matches 
WHERE status = 'Match Finished' 
  AND DATE(updated_at) = CURDATE()
ORDER BY updated_at DESC;
```

---

## 📊 API Football Pro - Límites

| Métrica | Valor |
|---------|-------|
| Requests/día | 7500 |
| Requests/hora | ~312 |
| Requests/minuto | ~5 |
| Status de hoy | 11/7500 usados |

**Análisis:**
- Actualizar 5 partidos = 1 request
- Con 7500 requests podemos actualizar 37,500 partidos/día
- Headroom: ABUNDANTE ✅

---

## ⚠️ Troubleshooting

### Problem: "No jobs available to process"
**Solución:** Es normal. Significa que no hay jobs en la queue. Ejecutar:
```bash
php artisan tinker
> dispatch(new \App\Jobs\UpdateFinishedMatchesJob);
```

### Problem: "cURL error 60: SSL certificate problem"
**Solución:** Ya está arreglado con `withoutVerifying()` en desarrollo. En producción, certificados SSL serán validados correctamente.

### Problem: "Rate limited by API Football"
**Solución:** Esperar 60 segundos. El job reintentar automáticamente (3 intentos).

### Problem: "Gemini rate limited"
**Solución:** API Football PRO es primaria, Gemini es secundaria. Con API Football funcionando, Gemini no será usado.

---

## 🎯 Casos de Uso

### Caso 1: Testing manual
```bash
# 1. Ejecutar queue worker
php artisan queue:work

# 2. En otra terminal, disparar job
php artisan tinker
> dispatch(new \App\Jobs\UpdateFinishedMatchesJob);

# 3. Ver logs
tail -f storage/logs/laravel.log
```

### Caso 2: Actualizar un partido específico
```bash
php artisan tinker
> $match = App\Models\FootballMatch::find(440);
> app(App\Services\FootballService::class)->updateMatchFromApi($match->id);
```

### Caso 3: Ver estado de la API
```bash
php test-api-pro.php
# Muestra: Plan, estado de suscripción, requests disponibles
```

---

## ✅ Checklist Pre-Producción

- [ ] `.env` tiene `FOOTBALL_API_KEY` con clave PRO
- [ ] `QUEUE_CONNECTION` está configurado (database/redis)
- [ ] `php artisan config:cache` fue ejecutado
- [ ] `php artisan migrate` completó exitosamente
- [ ] Queue worker se ejecuta sin errores
- [ ] API Football está conectada (`php test-api-pro.php`)
- [ ] Logs de Laravel están escribiendo correctamente
- [ ] Base de datos tiene permisos de lectura/escritura
- [ ] Cron job de scheduler está configurado (si usa scheduler)
- [ ] Supervisor/Systemd/PM2 está configurado para auto-reinicio

---

## 📞 Soporte

### Ver estado completo
```bash
php artisan tinker

# Ver configuración
> config('services.football');

# Ver estado de API
> app(App\Services\FootballService::class)->status();

# Procesar un partido
> app(App\Services\FootballService::class)->updateMatchFromApi(440);
```

---

**Última actualización:** 23-01-2026  
**Versión:** API Football PRO v1.0  
**Status:** Production Ready ✅

