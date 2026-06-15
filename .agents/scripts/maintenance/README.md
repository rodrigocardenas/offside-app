# Maintenance Scripts

Este directorio contiene scripts de mantenimiento y diagnóstico que se crearon durante la resolución de problemas de sincronización de puntos, verificación de partidos del Mundial y auditoría de seguridad.

## Scripts Disponibles

- **`recalc_points.sh`**:
  Ejecuta un bloque de PHP usando `php artisan tinker` que recalcula por completo la columna `points` en la tabla `group_user` para todos los grupos de la plataforma, basándose en la suma real de la columna `points_earned` de la tabla `answers` de cada usuario. Muy útil si hay una desincronización de puntajes a nivel masivo.

- **`fix_all_groups.php`**:
  Script PHP que soluciona IDs externos incorrectos de equipos y fuerza la re-evaluación de los puntajes para los grupos mediante el Job oficial de Laravel (`VerifyAllQuestionsJob`).

- **`force_verify_wc.php`**:
  Script utilitario que toma un rango de `match_id` (por ejemplo, del 2027 al 2036) y fuerza la ejecución sincrónica de `UpdateFinishedMatchesJob` y `VerifyAllQuestionsJob` saltándose las ventanas de tiempo normales de los Cron Jobs. Sirve para forzar la actualización de resultados de partidos que se saltaron o quedaron atrapados por problemas de paginación.

- **`scan_server.sh`**:
  Un script de auditoría de seguridad que se ejecuta vía SSH hacia el servidor EC2 de producción. Revisa crontabs (de root, www-data y ubuntu), llaves SSH (`authorized_keys`), procesos sospechosos (mineros como xmrig, kinsing, pulseadio), y busca firmas de webshells (eval, base64_decode, system) en las carpetas `public/` y `app/`.
