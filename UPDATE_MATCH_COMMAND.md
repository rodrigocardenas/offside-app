# 🎯 Comando: Actualizar Partido Específico

## Uso Rápido

```bash
php artisan app:update-match-status {match_id}
```

## Descripción

Actualiza un partido específico por su ID con:
- ✅ **Status** - Estado del partido (Not Started, In Play, Match Finished, etc)
- ✅ **Resultado** - Scores (home_team_score, away_team_score)
- ✅ **Eventos** - Goles y acciones importantes (si están disponibles)
- ✅ **Estadísticas** - Datos del partido desde Football-Data.org

## Ejemplos

### Actualizar un partido específico

```bash
php artisan app:update-match-status 446
```

**Salida:**

```
╔════════════════════════════════════════════════════════════╗
║ Actualizando Partido Específico                            ║
╚════════════════════════════════════════════════════════════╝

Partido: FC København vs SSC Napoli
Fecha: 2026-01-20 21:00
Liga: CL
External ID: 552038

Fixture ID: 552038

Obteniendo datos de Football-Data.org...
Obteniendo eventos...
Obteniendo estadísticas...

Actualizando base de datos...

╔════════════════════════════════════════════════════════════╗
║ ✅ ACTUALIZACIÓN COMPLETADA                                 ║
╠════════════════════════════════════════════════════════════╣
  Resultado: 1 - 1
  Status: Match Finished
  Eventos: No disponibles
  Estadísticas: ✓
╚════════════════════════════════════════════════════════════╝
```

## Casos de Uso

### 1. Forzar actualización de un partido
Si un partido quedó con status antiguo:
```bash
php artisan app:update-match-status 123
```

### 2. Recuperar datos de un partido específico
Si necesitas sincronizar un match en particular:
```bash
php artisan app:update-match-status 456
```

### 3. Verificar detalles de un partido
Ver qué datos se obtuvieron desde Football-Data:
```bash
php artisan app:update-match-status 789
```

## Requisitos

- El partido debe existir en la BD
- El partido debe tener un `external_id` válido (preferiblemente numérico de Football-Data.org)
- Acceso a Football-Data.org API (requiere FOOTBALL_DATA_API_KEY en .env)

## Lo que Actualiza

| Campo | Fuente | Descripción |
|-------|--------|------------|
| `status` | Football-Data.org | Mapeo de estado (FINISHED → Match Finished) |
| `home_team_score` | Football-Data.org | Goles del equipo local |
| `away_team_score` | Football-Data.org | Goles del equipo visitante |
| `score` | Calculado | Formato "X - Y" |
| `home_team` | Football-Data.org | Nombre oficial del equipo |
| `away_team` | Football-Data.org | Nombre oficial del equipo |
| `external_id` | Normalizado | Asegura formato numérico |
| `events` | Football-Data.org (opcional) | Goles y acciones si están disponibles |
| `statistics` | Football-Data.org (opcional) | Datos estadísticos si están disponibles |

## Registros

Todos los cambios se registran en `storage/logs/laravel.log`:

```
[2026-01-23 14:30:15] local.INFO: Partido actualizado manualmente desde Football-Data.org 
{"match_id":446,"teams":"FC København vs SSC Napoli","score":"1 - 1","status":"Match Finished","has_events":false,"has_statistics":true}
```

## Diferencias con Job Automático

| Aspecto | `app:update-match-status` | `UpdateFinishedMatchesJob` |
|--------|-------------------------|--------------------------|
| **Ejecución** | Manual | Automática (cada hora) |
| **Alcance** | Un partido | Múltiples partidos |
| **Velocidad** | Inmediata | Procesado en cola |
| **Uso** | Debug, manual, específico | Producción, masivo |

---

**Útil para:** Sincronización manual, testing, recuperación de fallos, verificación de datos.
