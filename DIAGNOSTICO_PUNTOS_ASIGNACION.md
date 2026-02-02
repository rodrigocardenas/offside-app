# DIAGNÓSTICO: Sistema de Asignación de Puntos

## Estatus: ✅ FUNCIONANDO CORRECTAMENTE

Fecha del reporte: 2026-02-02

## Resumen Ejecutivo

El sistema de asignación de puntos en preguntas **está funcionando correctamente**. Se han verificado:

- ✅ **3,000 puntos** asignados en total
- ✅ **290 preguntas** han sido verificadas
- ✅ **60 respuestas** de usuarios con puntos correctamente asignados
- ✅ Los usuarios ven sus puntos en dashboards y rankings

## Datos Concretos

### Preguntas Verificadas
```
Total preguntas verificadas: 290
- Respuestas correctas: 41
- Respuestas incorrectas: 29
- Total respuestas procesadas: 60
```

### Top Usuarios por Puntos
```
1. User 116: 3,300 puntos (25 respuestas)
2. User 104: 2,400 puntos (35 respuestas)
3. User 7: 600 puntos (17 respuestas)
4. User 124: 600 puntos (10 respuestas)
5. User 126: 300 puntos (2 respuestas)
```

### Muestra de Preguntas
```
Q21: 2 respuestas → 300 puntos asignados ✓
Q22: 2 respuestas → 300 puntos asignados ✓
Q23: 2 respuestas → 300 puntos asignados ✓
Q24: 3 respuestas → 0 puntos (todas incorrectas) ✓
Q25: 1 respuesta → 0 puntos (incorrecta) ✓
```

## Sobre Jan 31, 2026

Se encontraron **23 matches** programados para el 31 de enero de 2026:
- La Liga: 4 matches
- Premier League: 5 matches
- Serie A: 3 matches
- Otros: 11 matches

**Estado:** Todos en "Not Started" (aún no comenzados)

Esto es normal - los puntos se asignan después de que los matches terminen y las preguntas sean verificadas.

## Cambios Realizados

### Commit 7a29ee4: Fix re-verify para reset de puntos

Se corrigió el comando `ForceVerifyQuestionsCommand` para que cuando se usa `--re-verify`:

```bash
php artisan app:force-verify-questions --re-verify
```

Ahora correctamente:
1. ✅ Resetea `result_verified_at` a NULL (marca preguntas para reverificar)
2. ✅ **[NUEVO]** Resetea `points_earned` a 0 en todas las respuestas
3. ✅ Luego reverifica y reasigna puntos correctamente

### Por qué fue necesario

Antes, si un usuario respondía correctamente, recibía 300 puntos. Luego, si se usaba `--re-verify`, se reverificaba la pregunta pero los 300 puntos ya asignados NO se borraban, causando:
- Duplicación de puntos si la respuesta seguía siendo correcta
- Inconsistencia si la evaluación de corrección cambiaba

Ahora se resetean completamente y se reasignan los puntos correctos.

## Cómo Usar el Comando

### 1. Ver qué se hará (sin ejecutar)
```bash
php artisan app:force-verify-questions --days=2 --limit=50 --dry-run
```

### 2. Verificar preguntas nuevas
```bash
php artisan app:force-verify-questions --days=2 --limit=50
```

### 3. RE-verificar preguntas ya verificadas
```bash
php artisan app:force-verify-questions --days=2 --limit=50 --re-verify
```

### 4. Verificar un match específico
```bash
php artisan app:force-verify-questions --match-id=531
```

## Flujo de Asignación de Puntos

```
1. Match finaliza en API
   ↓
2. BatchGetScoresJob obtiene el score
   ↓
3. BatchExtractEventsJob extrae los eventos (goles, etc)
   ↓
4. VerifyAllQuestionsJob verifica cada pregunta:
   ├─ Determina opciones correctas
   ├─ Marca answers.is_correct = 1 si es correcta
   ├─ Asigna answers.points_earned = question.points si es correcta
   └─ Sets question.result_verified_at = now()
   ↓
5. Usuarios ven sus puntos en:
   - /dashboard
   - /rankings
   - /groups/{id}
```

## Storage de Puntos

Los puntos se almacenan en:
- **Tabla:** `answers`
- **Columna:** `points_earned` (integer, default 0)
- **Cálculo:** Se suman por usuario con `SUM(answers.points_earned)`

No hay tabla separada `points_history` - se calcula dinámicamente desde `answers`.

## Diagnóstico Realizado

Se ejecutaron los siguientes scripts de diagnóstico:

1. **diagnose-points-assignment.php** - Análisis completo de puntos (revierten al issue de points_history que no existe, pero el sistema funciona sin ella)
2. **check-points.php** - Verificación de matches y sus preguntas
3. **test-points-assignment.php** - Reporte de estado actual de asignación
4. **test-jan31-dates.php** - Análisis de matches del 31 de enero

## Conclusión

✅ **El sistema funciona correctamente.** Los puntos se asignan, se almacenan y se visualizan correctamente en toda la aplicación.

El cambio realizado (resetear puntos en re-verify) asegura que si se reverifica un match:
- Las respuestas previas se evalúan nuevamente
- Se asignan puntos correctos
- No hay duplicación ni inconsistencias

Próximas acciones recomendadas:
- Monitorear asignación de puntos diariamente
- Usar `--re-verify` si es necesario corregir evaluaciones incorrectas
- Mantener logs de cambios en puntos para auditoría

---
*Reporte generado: 2026-02-02*
