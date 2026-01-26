# 🧪 Testing Rápido - Bug #9

## ✅ Verificación de Implementación

### 1. Frontend - Componente de Preguntas

**Archivo:** `resources/views/components/groups/group-match-questions.blade.php`

- [x] Línea ~88: Variable `$matchHasStarted` detecta partido iniciado
- [x] Línea ~91-103: Banner rojo aparece cuando `$matchHasStarted = true`
- [x] Línea ~108: Condición incluye `&& !$matchHasStarted`

**Verificar:**
```bash
grep -n "matchHasStarted" resources/views/components/groups/group-match-questions.blade.php
```

### 2. Backend - Controller

**Archivo:** `app/Http/Controllers/QuestionController.php`

- [x] Línea ~95-118: Nueva validación en método `answer()`
- [x] Compara `football_match->date <= Carbon::now()`
- [x] Lanza `QuestionException` con código `match_already_started`

**Verificar:**
```bash
grep -n "matchHasStarted\|match_already_started" app/Http/Controllers/QuestionController.php
```

---

## 🧪 Casos de Prueba Manual

### TEST 1: Pregunta con Partido Futuro ✅

```
SETUP:
  - Crear pregunta predictiva con partido a las 19:30
  - Fecha actual: 14:00
  - Usuario: logueado

PASO 1: Ir a groups.show
  ✅ Debe verse el formulario de respuesta
  ✅ Banner rojo NO debe aparecer
  ✅ Las opciones deben estar clickeables

PASO 2: Hacer clic en una opción
  ✅ Se debe guardar la respuesta
  ✅ Se debe ver confirmación
  ✅ Sin errores en logs
```

### TEST 2: Pregunta con Partido en Progreso ❌

```
SETUP:
  - Crear pregunta predictiva con partido a las 19:30
  - Fecha actual: 20:15 (45 minutos después del inicio)
  - Usuario: logueado

PASO 1: Ir a groups.show
  ✅ Debe verse el banner ROJO con:
     - 🔒 icono
     - "El partido ha comenzado"
     - "No puedes responder predicciones..."
  ✅ NO debe verse el formulario de respuesta
  ✅ Debe verse la sección de resultados
  ✅ Debe verse respuesta anterior si la hay

PASO 2: Intentar enviar POST directamente (curl/postman)
  ✅ Backend debe rechazar con: 422 Unprocessable Entity
  ✅ Mensaje: "El partido ya ha comenzado"
  ✅ Log debe registrar intentos de fraude
```

### TEST 3: Pregunta ya Respondida Pre-Inicio ✅

```
SETUP:
  - Usuario ya respondió pregunta
  - Partido aún no comienza (futuro)

PASO 1: Ir a groups.show
  ✅ Ver respuesta guardada con badge de confirmación
  ✅ Banner rojo NO debe aparecer
  ✅ Debe poder modificar respuesta
```

### TEST 4: Pregunta Respondida Post-Inicio ❌

```
SETUP:
  - Usuario respondió antes del partido
  - Ahora el partido ya comenzó

PASO 1: Ir a groups.show
  ✅ Ver respuesta guardada
  ✅ Ver banner ROJO
  ✅ NO debe poder modificar
```

---

## 📝 Verificación en Logs

### Caso de Éxito
```
[2026-01-26 20:15:32] local.INFO: Respuesta guardada o actualizada: 123 - 456
```

### Caso de Intento Fallido
```
[2026-01-26 20:15:32] local.WARNING: Intento de responder pregunta predictiva después del inicio del partido {
  "user_id": 5,
  "question_id": 123,
  "match_date": "2026-01-26 19:30:00",
  "current_time": "2026-01-26 20:15:00"
}
```

---

## 🔍 Búsqueda Rápida en BD

```sql
-- Ver preguntas predictivas con partidos futuros
SELECT q.id, q.title, fm.date, NOW() as now
FROM questions q
JOIN football_matches fm ON q.match_id = fm.id
WHERE q.type = 'predictive'
AND q.category = 'predictive'
ORDER BY fm.date ASC;

-- Ver respuestas en últimas 2 horas
SELECT u.name, q.title, a.created_at, fm.date
FROM answers a
JOIN users u ON a.user_id = u.id
JOIN questions q ON a.question_id = q.id
LEFT JOIN football_matches fm ON q.match_id = fm.id
WHERE a.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
ORDER BY a.created_at DESC;
```

---

## 🚨 Debugging

### Si no funciona el bloqueo frontend:

1. **Verificar variable:**
```php
@php
    echo "matchHasStarted: " . ($matchHasStarted ? 'true' : 'false');
    echo " | Match date: " . $question->football_match->date;
    echo " | Now: " . now();
@endphp
```

2. **Limpiar cache:**
```bash
php artisan cache:clear
php artisan view:clear
```

3. **Revisar errores en browser:**
```javascript
// Console DevTools (F12)
console.log('Preguntas renderizadas:', document.querySelectorAll('[id^="question"]').length);
```

### Si el backend permite responder igual:

1. **Verificar exception llega:**
```php
// En QuestionController, antes de la validación
Log::info('Verificando partido', ['match_date' => $question->football_match->date]);
```

2. **Testing directo con curl:**
```bash
curl -X POST "http://localhost:8000/api/questions/123/answer" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"question_option_id": 456}'
```

---

## ✅ Checklist Final

- [ ] Componente muestra banner cuando partido inició
- [ ] Formulario desaparece cuando partido inició
- [ ] Backend rechaza respuestas post-inicio
- [ ] Logs registran intentos sospechosos
- [ ] Respuestas previas se muestran correctamente
- [ ] No hay errores en DevTools
- [ ] No hay SQL errors en logs
- [ ] Cache functions correctamente
- [ ] Funciona en diferentes zonas horarias
- [ ] Testing en dispositivo móvil (si aplica)

---

## 📋 Comandos Útiles

```bash
# Ver últimos errors
tail -f storage/logs/laravel.log | grep -i "question\|match\|exception"

# Limpiar todo
php artisan cache:clear && php artisan view:clear && php artisan config:clear

# Ejecutar migraciones (si es necesario)
php artisan migrate:refresh --seed

# Testing específico
php artisan tinker
> $q = Question::find(123);
> $q->football_match->date;
> now();
```

