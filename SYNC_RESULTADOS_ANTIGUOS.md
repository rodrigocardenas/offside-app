# 📊 Sincronización de Resultados Antiguos

## Problema
Partidos que fueron creados con la API anterior tienen external_id en formato antiguo o no sincronizado con Football-Data.org.

## Solución
El nuevo sistema usa **Football-Data.org como fuente oficial**. Para recuperar resultados de partidos antiguos:

### 1️⃣ Comando Automático (Recomendado)

```bash
# Última opción: recuperar últimos 30 días
php artisan app:recover-old-results

# Especificar rango personalizado
php artisan app:recover-old-results --days=60      # Últimos 60 días
php artisan app:recover-old-results --days=180     # Últimos 6 meses
php artisan app:recover-old-results --days=365     # Todo el año
```

### 2️⃣ Qué hace el comando

- ✅ Busca partidos con fecha pasada que aún no tienen status "Match Finished"
- ✅ Consulta Football-Data.org para obtener scores reales
- ✅ Actualiza campos:
  - `home_team_score` / `away_team_score`
  - `score` (formato "X - Y")
  - `status` (mapea a "Match Finished", "Postponed", etc)
  - `external_id` (normaliza a ID de Football-Data.org)
- ✅ Mantiene rate limiting para no sobrecargar API (1s entre requests)

### 3️⃣ Ejemplo de ejecución

```
╔════════════════════════════════════════════════════════════╗
║ Recuperando resultados de partidos antiguos (últimos 30 días)
╚════════════════════════════════════════════════════════════╝

Partidos encontrados para actualizar: 2

 2/2 [============================] 100%

╔════════════════════════════════════════════════════════════╗
║ RESUMEN                                                    ║
╠════════════════════════════════════════════════════════════╣
║ Partidos actualizados: 2 ✅
║ Partidos fallidos: 0 ❌
╚════════════════════════════════════════════════════════════╝
```

### 4️⃣ Integración con el Pipeline Automático

Una vez sincronizados, el sistema automático toma el control:

```
UpdateFinishedMatchesJob (cada hora)
  ↓
Busca partidos terminados con status ≠ "Match Finished"
  ↓
ProcessMatchBatchJob (procesa en lotes)
  ↓
updateMatchFromApi() → Football-Data.org
  ↓
Actualiza scores y status automáticamente
```

### 5️⃣ Logs

Revisa los logs para ver detalles de sincronización:

```bash
tail -f storage/logs/laravel.log | grep "Partido actualizado"
```

---

## 🎯 Caso de Uso Completo

### Primera vez (recuperar datos históricos)

```bash
# Traer todos los partidos del último año
php artisan app:recover-old-results --days=365

# Verificar que todos tengan external_id numérico
mysql -u root -proot offside2 -e \
  "SELECT COUNT(*) FROM football_matches WHERE external_id REGEXP '^[0-9]+$';"
```

### Mantenimiento periódico

```bash
# Ejecutar semanalmente en cron
# 02:00 cada domingo
0 2 * * 0 cd /path/to/project && php artisan app:recover-old-results --days=7
```

### Combinación con job automático

El comando es complementario a `UpdateFinishedMatchesJob`. Úsalo para:
- Sincronizar datos históricos al migrar de API
- Recuperar partidos que se perdieron por timeout de API
- Actualizar masivamente en horarios de bajo uso

---

## ⚙️ Detalles Técnicos

- **Ubicación**: `app/Console/Commands/RecoverOldResults.php`
- **Duración estimada**: ~1 segundo por partido (con rate limiting)
- **Errores ignorados**: Si un partido falla, continúa con el siguiente
- **Idempotente**: Puede ejecutarse múltiples veces sin duplicar datos
- **Logging**: Todos los cambios se registran en laravel.log
