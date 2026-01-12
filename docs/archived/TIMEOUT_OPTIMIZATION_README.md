# Optimización de Timeouts y Jobs

## Problema
El job `ProcessRecentlyFinishedMatchesJob` estaba dando timeout en producción porque procesaba demasiados partidos en un solo job.

## Solución Implementada

### 1. Configuración de Timeouts

#### Para Nginx:
Agregar al archivo de configuración de tu sitio (ej: `/etc/nginx/sites-available/tu-sitio`):

```nginx
# Timeout para conexiones del cliente
client_body_timeout 300s;
client_header_timeout 300s;

# Timeout para mantener conexiones abiertas
keepalive_timeout 300s;

# Timeout para lectura del cliente
send_timeout 300s;

# Timeout para FastCGI (si usas PHP-FPM)
fastcgi_read_timeout 300s;
fastcgi_send_timeout 300s;
fastcgi_connect_timeout 300s;
```

#### Para PHP:
Crear archivo `.user.ini` en el directorio raíz del proyecto:

```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
default_socket_timeout = 300
```

#### Para Laravel:
Ejecutar el comando de configuración automática:

```bash
php artisan app:configure-timeouts
```

### 2. Jobs Optimizados

Se dividió el job original en jobs más pequeños:

1. **ProcessRecentlyFinishedMatchesJob** (Coordinador)
   - Timeout: 10 minutos
   - Despacha los otros jobs con delays

2. **UpdateFinishedMatchesJob** (Actualización de partidos)
   - Timeout: 5 minutos
   - Divide partidos en lotes de 5

3. **ProcessMatchBatchJob** (Lotes de partidos)
   - Timeout: 2 minutos
   - Procesa máximo 5 partidos por lote

4. **VerifyQuestionResultsJob** (Verificación de preguntas)
   - Timeout: 5 minutos
   - Verifica resultados de preguntas finalizadas

5. **CreatePredictiveQuestionsJob** (Creación de preguntas)
   - Timeout: 5 minutos
   - Crea nuevas preguntas predictivas

### 3. Comandos de Prueba

#### Configurar timeouts:
```bash
php artisan app:configure-timeouts
```

#### Probar jobs optimizados:
```bash
# Probar todos los jobs
php artisan app:test-optimized-jobs

# Probar job específico
php artisan app:test-optimized-jobs --job=coordinator
php artisan app:test-optimized-jobs --job=update-matches
php artisan app:test-optimized-jobs --job=verify-questions
php artisan app:test-optimized-jobs --job=create-questions
php artisan app:test-optimized-jobs --job=batch
```

### 4. Pasos para Producción

1. **Configurar timeouts:**
   ```bash
   php artisan app:configure-timeouts
   ```

2. **Reiniciar servicios:**
   ```bash
   # Reiniciar Nginx
   sudo systemctl restart nginx
   
   # Reiniciar PHP-FPM
   sudo systemctl restart php8.1-fpm
   
   # Reiniciar workers de cola
   php artisan queue:restart
   ```

3. **Probar en producción:**
   ```bash
   php artisan app:test-optimized-jobs --job=coordinator
   ```

4. **Monitorear logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### 5. Ventajas de la Optimización

- **Menor tiempo de ejecución:** Cada job tiene un timeout específico
- **Mejor manejo de errores:** Si falla un lote, no afecta a los demás
- **Rate limiting:** Delays entre requests para evitar límites de API
- **Escalabilidad:** Fácil agregar más workers para procesar más lotes
- **Monitoreo:** Logs detallados para cada etapa del proceso

### 6. Monitoreo

Revisar logs para verificar que los jobs funcionan correctamente:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "(Job|Lote|Partido)"

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all
```

### 7. Configuración de Horizon (Opcional)

Si usas Laravel Horizon, asegúrate de que esté configurado correctamente:

```bash
# Verificar configuración de Horizon
php artisan horizon:status

# Reiniciar Horizon
php artisan horizon:terminate
php artisan horizon
``` 
