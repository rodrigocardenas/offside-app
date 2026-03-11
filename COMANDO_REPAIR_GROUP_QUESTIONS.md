# Comando: repair:group-questions

## Descripción

Comando genérico que automatiza la reparación de preguntas en cualquier grupo cuando los evaluadores fallan o retornan resultados incorrectos.

## Uso

```bash
php artisan repair:group-questions --group=ID --date=YYYY-MM-DD [--force]
```

## Parámetros

- `--group=ID` (Requerido): ID del grupo a reparar
- `--date=YYYY-MM-DD` (Requerido): Fecha en formato YYYY-MM-DD
- `--force` (Opcional): Aplica cambios sin confirmación

## Funcionamiento

### Paso 1: Inspección
El comando busca todas las preguntas del grupo en la fecha especificada y las re-evalúa usando `QuestionEvaluationService`:

```
PASO 1: Inspeccionando estado actual de preguntas...
  Evaluando Q1180: ¿Cuál equipo tendrá más posesión...
    🔄 CAMBIO NECESARIO
  Evaluando Q1181: ¿Quién anotará el primer gol...
    ✓ Sin cambios
```

### Paso 2: Confirmación de Cambios
Muestra un resumen de los cambios propuestos antes de aplicarlos:

```
CAMBIOS PROPUESTOS:

📝 Pregunta 1180: ¿Cuál equipo tendrá más posesión en el partido?
   Match: Newcastle vs Barcelona (1-1)
   Opciones correctas actual: NINGUNA
   Opciones correctas evaluadas: 4073
   Detalles:
     [4072] Newcastle
     ✅ [4073] Barcelona ← MARCARÁ COMO CORRECTA
```

### Paso 3: Aplicación de Cambios
Actualiza la base de datos marcan las opciones correctas:

```
PASO 2: Aplicando cambios en base de datos...
  ✓ Pregunta 1180 actualizada
  ✓ Pregunta 1181 actualizada
  Total actualizadas: 2
```

### Paso 4: Recalculación de Puntos
Recalcula automáticamente los puntos de todos los usuarios basado en las opciones correctas actualiz adas:

```
PASO 3: Recalculando puntos de usuarios...
  ✓ Pregunta 1180: 3 correctas
  ✓ Pregunta 1181: 3 correctas
  
  ESTADÍSTICAS:
    Total respuestas: 12
    Respuestas correctas: 7
    Respuestas actualizadas: 4
    Porcentaje de acierto: 58.3%
```

## Ejemplos

### Reparar Group 129 del 10/03/2026 con confirmación
```bash
php artisan repair:group-questions --group=129 --date=2026-03-10
```

Mostrará cambios propuestos y preguntará:
```
¿Aplicar estos cambios? (yes/no): yes
```

### Reparar Group 129 sin confirmación
```bash
php artisan repair:group-questions --group=129 --date=2026-03-10 --force
```

Aplica cambios automáticamente sin preguntar.

### Reparar otro grupo
```bash
php artisan repair:group-questions --group=103 --date=2026-03-10 --force
```

## Casos de Uso

1. **Después de actualizar evaluadores**: Si cambian los métodos de evaluación en `QuestionEvaluationService`, usar este comando para re-evaluar preguntas existentes.

2. **Reparar fallos automáticos**: Si el sistema de evaluación automática falla para un grupo en una fecha específica.

3. **Auditoría**: Ejecutar sin `--force` para ver qué cambios se detectarían sin aplicarlos.

4. **Batch de reparaciones**: Crear scripts que reparen múltiples grupos:
   ```bash
   for date in "2026-03-10" "2026-03-11" "2026-03-12"; do
       php artisan repair:group-questions --group=129 --date=$date --force
   done
   ```

## Qué Verifica

El comando compara:

```
Evaluador retorna:   [4073, 4074]
BD tiene marcadas:   [4072]
                     ↓
                CAMBIO DETECTADO
                ↓
         Actualiza BD a [4073, 4074]
         Recalcula puntos de usuarios
```

## Seguridad

- Por defecto requiere confirmación del usuario antes de aplicar cambios
- Usa `--force` solo si estás seguro de los cambios
- Sin `--force`, puedes ver cambios propuestos primero
- Todos los cambios se registran en logs

## Mantenimiento

```bash
# Ver opción de ayuda
php artisan repair:group-questions --help

# Reparar sin output
php artisan repair:group-questions --group=129 --date=2026-03-10 --force > /tmp/repair.log

# Ver si hay cambios necesarios primero
php artisan repair:group-questions --group=129 --date=2026-03-10 | grep "CAMBIO"
```
