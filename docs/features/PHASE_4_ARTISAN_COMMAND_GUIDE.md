# Phase 4 Testing - Artisan Command Documentation

**Command:** `php artisan phase4:test-points-sync`  
**Purpose:** Test the complete Phase 4 points synchronization flow  
**Status:** ✅ Ready to use  
**Date Created:** April 20, 2026

---

## 📌 Overview

Este comando Artisan testea todo el flujo Phase 4 de sincronización de puntos:

1. **Crear datos de prueba** - Grupo, usuarios, preguntas, respuestas
2. **Simular verificación** - Asignar points_earned a respuestas correctas
3. **Validar sincronización** - Verificar que answers → group_user.points
4. **Probar rankings optimizados** - Verificar que no hay JOINs innecesarios
5. **Simular castigos Pre-Match** - Validar que funcionan correctamente
6. **Reporte final** - Mostrar estado de todas las pruebas

---

## 🚀 Usar el Comando

### Opción 1: Test básico (créa y prueba, sin limpiar)
```bash
php artisan phase4:test-points-sync
```

**Resultado:**
- Crea datos de prueba en la BD
- Ejecuta todas las validaciones
- Deja datos de prueba para inspection manual
- Muestra reporte en consola

### Opción 2: Test con limpieza automática
```bash
php artisan phase4:test-points-sync --clean
```

**Resultado:**
- Mismo flow pero elimina datos de prueba al finalizar
- Recomendado para CI/CD

### Opción 3: Con output verboso (para debugging)
```bash
php artisan phase4:test-points-sync -v
```

Muestra más detalles, incluyendo stack traces si hay errores.

---

## ✅ Output Esperado

```
=================================================================
  PHASE 4: POINTS SYNCHRONIZATION TEST
  Testing: answers.points_earned to group_user.points
=================================================================

[1] Preparando datos de prueba...
  OK - Grupo creado: ID 1
  OK - Usuario creado: Test User 1
  OK - Usuario creado: Test User 2
  OK - Usuario creado: Test User 3
  OK - Partido creado
  OK - Pregunta creada
  OK - Respuestas creadas (usuario 1 acertara, otros no)

[2] Verificar sincronizacion en tiempo real (Phase 1)...
  Estado ANTES de verificacion:
  Sincronizando puntos a group_user...
  Estado DESPUES de sincronizacion:
    Test User 1: points_earned=300, group=300 [OK]
    Test User 2: points_earned=0, group=0 [OK]
    Test User 3: points_earned=0, group=0 [OK]

[3] Verificar datos historicos sincronizados (Phase 2)...
  Verificando historico...
    Total en group_user: 300 pts
    Total en answers: 300 pts
    OK - Historico sincronizado

[4] Verificar rankings optimizados (Phase 4)...
  Verificando optimizacion...
    OK - Query optimizada (sin JOINs innecesarios)
    Usuarios ordenados:
      Test User 1: 300 pts
      Test User 2: 0 pts
      Test User 3: 0 pts

[5] Probar castigos Pre-Match con puntos...
  Puntos ANTES: 300
  Castigo aplicado: -50
  Puntos DESPUES: 250
  OK - Castigo aplicado correctamente
  OK - Proteccion contra negativos funcionando

=================================================================
REPORTE FINAL
=================================================================

RESULTADOS:
  Pruebas pasadas: 5
  Pruebas fallidas: 0

OK - TODAS LAS PRUEBAS PASARON
Phase 4 esta funcionando correctamente

Proximos pasos:
  1. Ejecutar tests: php artisan test
  2. Verificar en staging
  3. Deploy a produccion
```

---

## 🔍 Qué Valida Este Comando

### PASO 1: Setup de Datos
- Crea grupo, 3 usuarios, partido, pregunta, 3 respuestas
- Valida que todas las entidades se crear correctamente

### PASO 2: Sincronización en Tiempo Real (Phase 1)
- Simula VerifyAllQuestionsJob
- Asigna points_earned basado en respuesta correcta/incorrecta
- Sincroniza a group_user.points
- **Validación:** points_earned === group_user.points

### PASO 3: Sincronización Histórica (Phase 2)
- Verifica SUM(answers.points_earned) === SUM(group_user.points)
- Detectar inconsistencias históricas
- **Validación:** Totales coincidentes

### PASO 4: Rankings Optimizados (Phase 4)
- Ejecuta Group::rankedUsers()->get()
- Inspecciona SQL query
- **Validación:** No hay LEFT JOIN a answers/questions, no hay GROUP BY
- Verifica orden correcto de usuarios

### PASO 5: Castigos Pre-Match
- Simula aplicar castigo de 50 puntos
- Valida que puntos se restan correctamente
- **Validación:** max(0, original - castigo)

---

## ❌ Posibles Errores

### Error: "FALLO - Desincronizacion: Usuario X"
**Significa:** answers.points_earned !== group_user.points

**Solución:**
```bash
# Ejecutar Phase 1
php artisan migrate

# Esperar VerifyAllQuestionsJob
# O ejecutar manually
php artisan test
```

### Error: "FALLO - Query tiene JOINs innecesarios"
**Significa:** rankedUsers() no está usando la optimización Phase 4

**Solución:**
- Verificar que app/Models/Group.php::rankedUsers() está actualizado
- Ejecutar: php artisan config:cache

### Error: "FALLO - Inconsistencia detectada"
**Significa:** group_user.points está fuera de sync con answers.points_earned

**Solución:**
```bash
# Ejecutar migración Phase 2
php artisan migrate

# Verificar resultado
php artisan phase4:test-points-sync
```

---

## 🧪 Integración con Tests Automáticos

Para CI/CD:
```yaml
# .github/workflows/test.yml
- name: Test Phase 4 Points Sync
  run: php artisan phase4:test-points-sync --clean
```

---

## 📊 Datos de Prueba

El comando crea:
- **1 Grupo** de categoría 'private'
- **3 Usuarios** con emails test@test.local
- **1 Partido** simulado (Test FC vs Demo FC)
- **1 Pregunta** con 3 opciones (1 correcta, 2 incorrectas)
- **3 Respuestas** (usuario 1 acierta, otros fallan)

**Nota:** Todos los datos incluyen timestamp para evitar conflictos entre ejecuciones múltiples.

---

## 🧹 Limpieza Manual

Si ejecutas sin `--clean` y quieres limpiar después:

```bash
# Ver datos de prueba
php artisan tinker
>>> App\Models\Group::where('name', 'like', 'Phase4%')->get();

# Eliminar
>>> App\Models\Group::where('name', 'like', 'Phase4%')->delete();
```

---

## 🔗 Comandos Relacionados

Otros commands para testing del flujo de puntos:
- `php artisan test:points-verification` - Test Points verification
- `php artisan check:points-issue` - Check points assignment issues
- `php artisan simulate:points-assignment` - Simulate points assignment

---

## 📝 Comandos Alternativos (Legacy)

Si este comando no funciona, puedes usar:
```bash
# Test manual en tinker
php artisan tinker

# Dentro de tinker:
$group = App\Models\Group::find(XX);
$group->rankedUsers()->limit(3)->get();
```

---

## ✅ Checklist Pre-Deploy

Antes de deployar a producción:

- [ ] Ejecutar: `php artisan phase4:test-points-sync --clean`
- [ ] Resultado: "TODAS LAS PRUEBAS PASARON"
- [ ] Ejecutar tests: `php artisan test`
- [ ] Verificar en staging: funcionalidad de rankings visible
- [ ] Verificar castigos Pre-Match: aplican puntos correctamente
- [ ] Performance: rankings cargan rápido

Si todos pasan → Listo para producción ✅
