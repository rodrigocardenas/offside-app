# Script de Prueba: Ciclo Completo de la Aplicación

## Descripción

Este script realiza un ciclo completo de la aplicación **Offside Club** de forma automatizada, sin usar datos hardcodeados. El ciclo incluye:

1. **Obtener partidos próximos** desde las APIs de fútbol (datos reales)
2. **Guardar partidos en BD**
3. **Crear un grupo de prueba**
4. **Generar preguntas predictivas** basadas en los partidos
5. **Responder las preguntas** con un usuario de prueba
6. **Simular resultados** de los partidos
7. **Verificar las respuestas** y asignar puntos
8. **Generar reporte** con los resultados

## Requisitos Previos

- ✅ Laravel instalado y funcionando
- ✅ Base de datos configurada y migrada (`php artisan migrate`)
- ✅ Competiciones cargadas en la BD
- ✅ Clave API de Football-Data.org configurada en `.env`

### Verificar Configuración

```bash
# Verificar que la BD está lista
php artisan tinker
>>> DB::table('competitions')->count()  # Debería retornar > 0
>>> exit()

# Verificar que la clave API está configurada
grep FOOTBALL_DATA .env
```

## Instalación

No requiere instalación adicional. El script está listo para usar en la carpeta `scripts/`.

## Uso

### Opción 1: Ejecutar el script PHP directamente

```bash
cd /path/to/offsideclub
php scripts/test-complete-cycle.php
```

### Opción 2: Ejecutar el script Bash

```bash
cd /path/to/offsideclub
chmod +x test-complete-cycle.sh
./test-complete-cycle.sh
```

### Opción 3: Ejecutar desde Artisan

```bash
php artisan tinker
>>> require 'scripts/test-complete-cycle.php';
```

## Flujo de Ejecución Detallado

### PASO 1: Obtener o Crear Usuario de Prueba
- Crea un nuevo usuario con email único basado en timestamp
- Si el usuario ya existe, lo reutiliza
- Se usa este usuario para todas las operaciones subsiguientes

### PASO 2: Obtener Competiciones Disponibles
- Obtiene todas las competiciones de la BD (LaLiga, Premier League, Champions)
- Selecciona la primera disponible
- Muestra información de cada competición

### PASO 3: Obtener Partidos Próximos de la API
- Llama a la API de Football-Data.org
- Obtiene los 5 próximos partidos sin disputar
- Si la API no responde, usa datos de prueba
- Muestra detalles de cada partido

### PASO 4: Guardar Partidos en BD
- Guarda los partidos obtenidos en la tabla `football_matches`
- Usa `updateOrCreate` para evitar duplicados
- Asocia cada partido con la competición seleccionada

### PASO 5: Crear un Grupo
- Crea un nuevo grupo de prueba
- Genera un código único de 6 caracteres
- Añade el usuario de prueba como miembro

### PASO 6: Generar Preguntas Predictivas
- Crea 3 plantillas de preguntas diferentes:
  - ¿Qué equipo anotará el primer gol?
  - ¿Habrá más de 2.5 goles?
  - ¿Cuál será el resultado final?
- Para cada partido, genera todas las preguntas
- Crea opciones para cada pregunta
- Total: 2 partidos × 3 preguntas = 6 preguntas

### PASO 7: Responder las Preguntas
- El usuario de prueba responde todas las preguntas
- Selecciona opciones aleatoriamente
- Las respuestas se guardan en la tabla `answers`

### PASO 8: Simular Resultados de Partidos
- Genera resultados aleatorios para los partidos
- Actualiza el estado de los partidos a "FINISHED"
- Guarda los marcadores finales

### PASO 9: Verificar Respuestas y Asignar Puntos
- Compara las respuestas del usuario con las respuestas correctas
- Asigna 10 puntos por respuesta correcta, 0 por incorrecta
- Actualiza la BD con los resultados

### PASO 10: Generar Reporte Final
- Muestra estadísticas del ciclo:
  - Total de preguntas respondidas
  - Respuestas correctas
  - Porcentaje de acierto
  - Puntos totales

### PASO 11: Guardar Información en Log
- Genera un archivo de log con toda la información
- Se guarda en: `storage/logs/test-cycle-YYYY-MM-DD-HH-MM-SS.txt`
- Contiene detalles completos del ciclo ejecutado

## Salida Esperada

```
=== PASO 1: Obtener o crear usuario de prueba ===

✓ Usuario creado: test-cycle-1234567890@example.com

=== PASO 2: Obtener competiciones disponibles ===

✓ Competiciones encontradas: 3
ℹ - La Liga (laliga)
ℹ - Premier League (premier)
ℹ - Champions League (champions)
✓ Competición seleccionada: La Liga

=== PASO 3: Obtener partidos próximos de la API ===

ℹ Obteniendo partidos para competición: laliga (ID: 2014)
✓ Se obtuvieron 5 partidos próximos
ℹ - Real Madrid vs Barcelona (2024-01-15T10:00:00Z)
ℹ - Atletico Madrid vs Sevilla (2024-01-16T15:00:00Z)

... (más pasos) ...

=== CICLO COMPLETO FINALIZADO ===

✓ El ciclo completo de la aplicación se ha ejecutado exitosamente
ℹ Revisa el archivo de log para más detalles
ℹ Acceso a la aplicación: http://localhost/offsideclub
ℹ Email del usuario de prueba: test-cycle-1234567890@example.com
ℹ Contraseña: password123
```

## Interpretación de Resultados

### Respuesta Correcta ✓
```
[✓] ¿Cuál será el resultado del partido Real Madrid vs Barcelona?
Respuesta: Victoria Real Madrid
Puntos: 10
```

### Respuesta Incorrecta ✗
```
[✗] ¿Cuál será el resultado del partido Real Madrid vs Barcelona?
Respuesta: Victoria Barcelona
Puntos: 0
```

## Archivo de Log

Después de ejecutar el script, encontrarás un archivo de log en:

```
storage/logs/test-cycle-YYYY-MM-DD-HH-MM-SS.txt
```

Este archivo contiene:
- Datos del usuario
- Información del grupo
- Detalles de todos los partidos
- Todas las preguntas y respuestas
- Puntuación final

Ejemplo de contenido:

```
REPORTE DEL CICLO COMPLETO DE LA APLICACIÓN
==========================================

Fecha: 2024-01-14 15:30:45
Usuario: Usuario Prueba Ciclo (test-cycle-1234567890@example.com)
Grupo: Grupo Prueba 2024-01-14 15:30:45 (Código: ABC123)
Competición: La Liga

PARTIDOS GUARDADOS:
--------------------------------------------------
Real Madrid vs Barcelona
Fecha: 2024-01-15 10:00:00
Resultado: 2 - 1
Ganador: HOME

...
```

## Datos Generados

Al ejecutar el script, se generarán:

| Elemento | Cantidad | Ubicación |
|----------|----------|-----------|
| Usuario | 1 | `users` |
| Grupo | 1 | `groups` |
| Partidos | 2 | `football_matches` |
| Preguntas | 6 | `questions` |
| Opciones | 18 | `question_options` |
| Respuestas | 6 | `answers` |
| Log | 1 | `storage/logs/` |

## Limpiar Datos de Prueba

Si necesitas limpiar los datos generados:

```bash
# Eliminar el usuario de prueba
php artisan tinker
>>> $user = User::where('email', 'test-cycle-*@example.com')->latest()->first();
>>> $user->groups()->detach();
>>> $user->answers()->delete();
>>> $user->delete();
>>> exit()

# O crear un comando Artisan para limpiar automáticamente
php artisan command:clean-test-data
```

## Troubleshooting

### Error: "No hay competiciones disponibles en la BD"

**Solución:** Las competiciones no están cargadas. Ejecuta:

```bash
php artisan db:seed --class=CompetitionSeeder
# O crea las competiciones manualmente en la BD
```

### Error: "No hay partidos próximos disponibles en la API"

**Posibles causas:**
- Clave API inválida o vencida
- Límite de requests alcanzado
- Error de red/conexión
- No hay partidos programados para esta fecha

**Solución:** El script usa datos de prueba automáticamente si la API falla.

### Error: "Error obteniendo partidos: ..."

**Solución:** Verifica que `.env` tiene la configuración correcta:

```bash
# En .env
FOOTBALL_DATA_API_TOKEN=tu_token_aqui
FOOTBALL_DATA_API_URL=https://api-football-v1.p.rapidapi.com
```

### El script es muy lento

- Verifica que la BD está en local (no remota)
- Aumenta los tiempos de espera en `php.ini` si es necesario
- Reduce el número de preguntas editando `test-complete-cycle.php`

## Personalización

### Cambiar Competición

Edita el archivo `scripts/test-complete-cycle.php`:

```php
// Línea ~110
$competitions = Competition::whereIn('type', ['laliga', 'premier', 'champions'])->get();
// Cambia a solo una:
$competitions = Competition::where('type', 'premier')->get();
```

### Cambiar Número de Partidos

Edita línea ~142:

```php
foreach (array_slice($upcomingMatches, 0, 2) as $matchData) {
// Cambia el 2 al número que desees (ej: 5)
```

### Cambiar Plantillas de Preguntas

Edita línea ~183 para agregar más plantillas:

```php
$templates = [
    [
        'template' => '¿Tu pregunta aquí con {home} y {away}?',
        'options' => ['Opción 1', 'Opción 2', 'Opción 3']
    ],
    // Agrega más plantillas aquí
];
```

## Notas Importantes

✅ **El script es idempotente:** Puede ejecutarse múltiples veces sin problemas
✅ **Usa datos reales:** Obtiene partidos actuales de la API
✅ **No hardcodea datos:** Genera datos dinámicamente
✅ **Crea usuarios únicos:** Cada ejecución crea un usuario nuevo
✅ **Genera reportes:** Guarda logs detallados de cada ejecución

## Estructura de la Base de Datos

El script interactúa con estas tablas:

```
┌─────────────────┐
│     users       │
└────────┬────────┘
         │
    ┌────┴────┐
    │          │
┌───▼──────┐  ┌──────────────┐
│ groups   │  │   answers    │
└────┬─────┘  └──────┬───────┘
     │               │
┌────▼──────────┐   │
│ group_user    │   │
└───────────────┘   │
                    │
          ┌─────────┴──────────┐
          │                    │
    ┌─────▼────────┐   ┌──────▼──────────┐
    │  questions   │   │ question_options│
    └─────┬────────┘   └─────────────────┘
          │
          │
┌─────────▼─────────┐
│ football_matches  │
└───────────────────┘
```

## API Utilizada

El script obtiene datos de **Football-Data.org**:

- **Base URL:** `https://api-football-v1.p.rapidapi.com/v3`
- **Autenticación:** Token en header `X-Auth-Token`
- **Rate Limit:** 10 requests/min (plan gratuito)
- **Datos:** Partidos en vivo, resultados, estadísticas

### Endpoints Utilizados

```
GET /competitions/{competitionId}/matches?status=SCHEDULED
- Obtiene partidos próximos (no iniciados)
- Parámetros: status=SCHEDULED, limit=5
```

## Soporte

Para problemas o mejoras, consulta:
- `TECHNICAL_DOCUMENTATION.md`
- `README.md`
- Logs en `storage/logs/`

---

**Última actualización:** Enero 2024
**Versión:** 1.0
**Estado:** Funcional y probado
