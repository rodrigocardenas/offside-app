# Ejemplos de Uso - Script de Ciclo Completo

## Ejemplos Básicos

### 1. Ejecutar el ciclo completo básico

```bash
php scripts/test-complete-cycle.php
```

**Qué hace:**
- Crea 1 usuario de prueba
- Obtiene 2 partidos de La Liga
- Crea 1 grupo
- Genera 3 preguntas por partido
- Responde todas las preguntas
- Simula resultados y asigna puntos

**Salida esperada:**
```
=== PASO 1: Obtener o crear usuario de prueba ===
✓ Usuario creado: test-cycle-1704078645@example.com
...
=== CICLO COMPLETO FINALIZADO ===
✓ El ciclo completo de la aplicación se ha ejecutado exitosamente
```

---

### 2. Ejecutar versión avanzada

```bash
php scripts/test-complete-cycle-advanced.php
```

**Diferencias con la versión básica:**
- Mejor estructura de clases
- Opciones de línea de comandos
- Modo verbose para más detalles
- Modo dry-run para ver qué haría
- Opción para limpiar datos anteriores

---

## Ejemplos Avanzados (Versión Advanced)

### 3. Crear múltiples usuarios de prueba

```bash
php scripts/test-complete-cycle-advanced.php --users=3
```

**Qué hace:**
- Crea 3 usuarios de prueba diferentes
- Cada usuario crea su propio grupo
- Cada usuario responde las preguntas

---

### 4. Usar múltiples competiciones

```bash
php scripts/test-complete-cycle-advanced.php --competitions=laliga,premier,champions
```

**Qué hace:**
- Obtiene partidos de La Liga, Premier League y Champions
- Mezcla partidos de diferentes competiciones
- Crea preguntas para todos los partidos

---

### 5. Aumentar número de partidos

```bash
php scripts/test-complete-cycle-advanced.php --matches=5
```

**Qué hace:**
- Obtiene 5 partidos en lugar de 2
- Genera más preguntas
- Más respuestas para evaluar

---

### 6. Aumentar número de plantillas de preguntas

```bash
php scripts/test-complete-cycle-advanced.php --templates=5
```

**Qué hace:**
- Crea 5 tipos diferentes de preguntas por partido
- Más variedad en las preguntas predictivas

---

### 7. Combinación de opciones

```bash
php scripts/test-complete-cycle-advanced.php \
  --users=2 \
  --matches=3 \
  --competitions=laliga,premier \
  --templates=4
```

**Qué hace:**
- 2 usuarios de prueba
- 3 partidos por competición
- 2 competiciones (La Liga + Premier)
- 4 plantillas de preguntas
- **Total: 2 usuarios × 3 partidos × 2 competiciones × 4 preguntas = 48 preguntas**

---

### 8. Modo verbose para más detalles

```bash
php scripts/test-complete-cycle-advanced.php --verbose
```

**Qué muestra:**
```
ℹ Competiciones: laliga
ℹ Partidos a crear: 2
ℹ Usuarios de prueba: 1
ℹ Plantillas de preguntas: 3
ℹ Modo verbose: SÍ
✓ Usuario creado: test-cycle-1704078645-0@example.com
ℹ Procesando: La Liga
ℹ Pregunta: ¿Qué equipo anotará el primer gol en Real Madrid vs Barcelona?
ℹ Respuesta guardada: Victoria Real Madrid
...
```

---

### 9. Modo dry-run (prueba sin cambios)

```bash
php scripts/test-complete-cycle-advanced.php --dry-run
```

**Qué hace:**
- Simula todo el proceso
- No realiza cambios en la BD
- Muestra qué se haría

**Salida:**
```
✓ Ejecutando en modo DRY-RUN. No se harán cambios.
ℹ [DRY-RUN] Se crearía usuario: test-cycle-1704078645-0@example.com
ℹ [DRY-RUN] Se guardaría partido
ℹ [DRY-RUN] Se crearía un grupo
ℹ [DRY-RUN] Se crearía una pregunta
```

---

### 10. Limpiar datos anteriores

```bash
php scripts/test-complete-cycle-advanced.php --clean
```

**Qué hace:**
- Elimina todos los usuarios de prueba anteriores
- Elimina sus respuestas, comentarios y grupos
- Luego ejecuta un ciclo nuevo

---

### 11. Test completo con todas las opciones

```bash
php scripts/test-complete-cycle-advanced.php \
  --users=2 \
  --matches=4 \
  --competitions=laliga,premier \
  --templates=5 \
  --verbose \
  --clean
```

---

## Workflows Recomendados

### Workflow 1: Testing Rápido

```bash
# Prueba rápida del ciclo completo
php scripts/test-complete-cycle.php

# Tiempo aprox: 5-10 segundos
# Datos generados: 1 usuario, 1 grupo, 2 partidos, 6 preguntas
```

### Workflow 2: Testing Exhaustivo

```bash
# Test exhaustivo con múltiples usuarios y competiciones
php scripts/test-complete-cycle-advanced.php \
  --users=3 \
  --matches=5 \
  --competitions=laliga,premier,champions \
  --templates=4 \
  --verbose \
  --clean

# Tiempo aprox: 15-30 segundos
# Datos generados: 3 usuarios, 3 grupos, ~15 partidos, ~180 preguntas
```

### Workflow 3: Validación antes de Deploy

```bash
# Validar que todo funciona antes de desplegar
php scripts/test-complete-cycle-advanced.php \
  --dry-run \
  --verbose

# Luego ejecutar de verdad
php scripts/test-complete-cycle-advanced.php

# Tiempo aprox: 10-15 segundos
```

### Workflow 4: Limpieza de datos de prueba

```bash
# Primero ver qué se limpiaría
php scripts/test-complete-cycle-advanced.php \
  --clean \
  --dry-run

# Luego limpiar de verdad
php scripts/test-complete-cycle-advanced.php --clean
```

---

## Casos de Uso Específicos

### Caso 1: Probar con una sola competición

```bash
php scripts/test-complete-cycle-advanced.php --competitions=premier
```

**Ideal para:**
- Pruebas de integración con Premier League
- Validar datos específicos de una liga

---

### Caso 2: Stress test con muchas preguntas

```bash
php scripts/test-complete-cycle-advanced.php \
  --users=5 \
  --matches=10 \
  --templates=5
```

**Ideal para:**
- Probar performance con muchos datos
- Validar que la BD aguanta
- Verificar que la UI no se congela

---

### Caso 3: Testing de ranking

```bash
# Crear usuarios y grupos de prueba
php scripts/test-complete-cycle-advanced.php --users=5

# Luego en la app:
# - Ir a http://localhost/groups/[grupo-id]/ranking
# - Ver que los usuarios aparecen con sus puntos
# - Verificar que el ranking es correcto
```

---

### Caso 4: Testing de notificaciones

```bash
# Crear datos de prueba
php scripts/test-complete-cycle-advanced.php \
  --users=2 \
  --matches=3

# Luego verificar:
# - Que se envíen notificaciones push
# - Que se guarden en la BD
# - Que la UI las muestre
```

---

### Caso 5: Testing de reportes

```bash
# Generar muchos datos
php scripts/test-complete-cycle-advanced.php \
  --users=10 \
  --matches=5 \
  --competitions=laliga,premier

# Luego:
# - Ir a reports/analytics
# - Verificar que se calculan correctamente
# - Validar gráficos y estadísticas
```

---

## Flujos de Integración

### Con CI/CD Pipeline

```bash
#!/bin/bash
# .github/workflows/test.yml

# Test de ciclo completo antes de merge
php scripts/test-complete-cycle-advanced.php \
  --dry-run \
  --verbose

if [ $? -ne 0 ]; then
  echo "Test cycle failed"
  exit 1
fi

# Si pasa el dry-run, ejecutar de verdad en BD de test
php scripts/test-complete-cycle-advanced.php \
  --clean
```

---

### Con script local de desarrollo

```bash
#!/bin/bash
# scripts/dev-test.sh

echo "Testing complete cycle..."
php scripts/test-complete-cycle-advanced.php \
  --users=2 \
  --matches=3 \
  --verbose

echo ""
echo "Opening logs..."
cat storage/logs/test-cycle-*.txt | tail -50
```

---

## Monitoreo y Debugging

### Ver logs en tiempo real

```bash
# Terminal 1: Ejecutar test
php scripts/test-complete-cycle-advanced.php --verbose

# Terminal 2: Ver logs
tail -f storage/logs/laravel.log

# Terminal 3: Ver BD
php artisan tinker
>>> User::where('email', 'like', 'test-cycle-%')->count()
>>> Group::where('name', 'like', 'Grupo Prueba%')->count()
```

---

### Debugging paso a paso

```bash
# Modo dry-run para ver qué haría
php scripts/test-complete-cycle-advanced.php --dry-run --verbose

# Si hay errores, verlos en:
storage/logs/laravel.log

# Limpiar y reintentar
php scripts/test-complete-cycle-advanced.php --clean

# Ejecutar de nuevo
php scripts/test-complete-cycle-advanced.php --verbose
```

---

## Preguntas Frecuentes

### P: ¿Cuánto tiempo tarda?

**R:** Depende del número de datos:
- Ciclo básico (1 usuario, 2 partidos): 5-10 segundos
- Ciclo avanzado (3 usuarios, 5 partidos): 15-30 segundos
- Stress test (10 usuarios, 10 partidos): 30-60 segundos

### P: ¿Dónde se guardan los datos?

**R:** Todos los datos se guardan en la BD que tengas configurada en `.env`:
- Usuarios → `users`
- Grupos → `groups`
- Partidos → `football_matches`
- Preguntas → `questions`
- Respuestas → `answers`
- Logs → `storage/logs/`

### P: ¿Cómo limpio los datos de prueba?

**R:**
```bash
# Opción 1: Usar la flag --clean
php scripts/test-complete-cycle-advanced.php --clean

# Opción 2: Manualmente en tinker
php artisan tinker
>>> User::where('email', 'like', 'test-cycle-%')->forceDelete();
>>> exit()
```

### P: ¿Puedo modificar las plantillas de preguntas?

**R:** Sí, edita el archivo y modifica el array `$templates`:

```php
// En test-complete-cycle.php, línea ~183
$templates = [
    [
        'template' => '¿Tu pregunta {home} vs {away}?',
        'options' => ['Opción 1', 'Opción 2']
    ],
];
```

### P: ¿Qué pasa si la API falla?

**R:** El script usa datos de prueba automáticamente. No fallaría.

---

## Tips y Trucos

### Tip 1: Ejecutar múltiples tests en paralelo

```bash
# Terminal 1
php scripts/test-complete-cycle-advanced.php --competitions=laliga &

# Terminal 2
php scripts/test-complete-cycle-advanced.php --competitions=premier &

# Terminal 3
php scripts/test-complete-cycle-advanced.php --competitions=champions &

wait
echo "All tests completed"
```

### Tip 2: Ver el progreso en tiempo real

```bash
# Terminal 1
php scripts/test-complete-cycle-advanced.php --verbose

# Terminal 2
watch -n 1 'php artisan tinker <<< "User::where(\"email\", \"like\", \"test-cycle-%\")->count(); exit();"'
```

### Tip 3: Exportar datos para análisis

```bash
# Después de ejecutar el test
php artisan tinker
>>> $answers = Answer::whereHas('user', fn($q) => $q->where('email', 'like', 'test-cycle-%'))->with('question')->get();
>>> $answers->to_array()
# Copiar output a Excel/Sheets
```

---

**Última actualización:** Enero 2024
