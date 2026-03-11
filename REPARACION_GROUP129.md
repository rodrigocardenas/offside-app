# INSTRUCCIONES: Reparar Group 129

## 📋 Resumen del Problema

Después del commit 9042d5f que removió compuertas de evaluación, las preguntas del Group 129 (Match 2003, 10-03-2026) se re-evaluaron pero con resultados aparentemente incorrectos. Solo 1 de 9 preguntas fue marcada como correcta.

## 🔧 Solución

Se han creado 5 herramientas artisan para diagnosticar y reparar automáticamente:

### Opción 1: Reparación Automática Completa (RECOMENDADO)

Ejecuta en tu servidor remoto:

```bash
ssh ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
cd /var/www/offsideclub
git pull origin main
php artisan fix:group-129
```

Este comando:
1. ✅ Valida los datos de Match 2003
2. ✅ Re-evalúa todas las 9 preguntas
3. ✅ Actualiza las opciones correctas automáticamente
4. ✅ Recalcula puntos de todos los usuarios
5. ✅ Muestra un resumen de cambios

**Duración estimada**: 30-60 segundos

---

### Opción 2: Reparación Paso a Paso (CON VALIDACIÓN)

Si prefieres validar en cada paso:

#### Paso 1: Inspeccionar datos
```bash
php artisan debug:match-2003
```

Verifica:
- ¿Equipos son Atletico Madrid vs Tottenham?
- ¿Resultado es 5-2?
- ¿Seed hay 9 preguntas en el grupo?
- ¿Opciones actuales son correctas?

#### Paso 2: Re-evaluar preguntas
```bash
php artisan app:evaluate-match-questions --match-id=2003 --force=true
```

Mostrará:
- Cada pregunta y su evaluación
- ⚠️ DIFERENCIAS si la opción correcta cambió
- ✓ IGUAL si ya estaba correcta

#### Paso 3: Recalcular puntos de usuarios
```bash
php artisan answers:reevaluate --group=129 --date=2026-03-10
```

Mostrará:
- Número de respuestas actualizadas
- Puntos totales recalculados
- Porcentaje de acierto

---

### Opción 3: Inspección sin Cambios (DIAGNÓSTICO SOLO)

Si solo quieres ver qué está mal sin cambiar nada:

```bash
php artisan debug:match-2003
php artisan verify:group-data --group=129 --match-id=2003
```

Estos comandos solo leen datos, no modifican nada.

---

## 📊 Resultados Esperados

### Antes de la Reparación
```
RESUMEN:
  Total respuestas: 45+ usuarios
  Respuestas correctas: ~1 (incorrectamente bajo)
  Puntos asignados: ~300 total
```

### Después de la Reparación
```
RESUMEN:
  Total respuestas: 45+ usuarios  
  Respuestas correctas: ~20-25 (esperado ~50%)
  Puntos asignados: 6000-7500 puntos
```

---

## ✅ Verificación Post-Reparación

Después de ejecutar cualquier opción, verifica:

### 1. En la Base de Datos
```sql
-- Ver opciones correctas de preguntas en Match 2003
SELECT q.id, q.title, qo.id, qo.text, qo.is_correct 
FROM questions q 
JOIN question_options qo ON q.id = qo.question_id 
WHERE q.match_id = 2003 AND q.group_id = 129
ORDER BY q.id, qo.id;

-- Ver puntos asignados a usuarios
SELECT u.name, SUM(a.points_earned) as total_points 
FROM users u 
JOIN answers a ON u.id = a.user_id 
JOIN questions q ON a.question_id = q.id 
WHERE q.group_id = 129 AND q.match_id = 2003
GROUP BY u.id
ORDER BY total_points DESC;
```

### 2. Via Dashboard
- Ir a Competition > Group 129
- Ver que usuarios ahora tienen puntos correctos
- Verificar que rankings se actualicen

### 3. Via Comando
```bash
php artisan verify:group-data --group=129 --match-id=2003
```

---

## 🚨 Troubleshooting

### "Match 2003 no encontrado"
```bash
# Buscar el match ID correcto
# En base de datos:
SELECT id, home_team, away_team, date FROM football_matches 
WHERE (home_team = "Atletico Madrid" OR away_team = "Atletico Madrid")
AND DATE(date) = "2026-03-10";
```

### "No hay preguntas encontradas"
```bash
# Verificar que existan preguntas en Group 129
SELECT COUNT(*) FROM questions 
WHERE group_id = 129 AND match_id = 2003;
```

### Cambios no se ven en UI
```bash
# Limpiar cache
php artisan cache:clear
php artisan view:clear

# Refrescar página en navegador (Ctrl+Shift+R)
```

---

## 📝 Notas Técnicas

### Qué hace cada comando

| Comando | Función | Modifica BD |
|---------|---------|------------|
| `debug:match-2003` | Inspecciona datos | NO |
| `verify:group-data` | Muestra estado actual | NO |
| `app:evaluate-match-questions` | Re-evalúa y actualiza opciones | SÍ |
| `answers:reevaluate` | Recalcula puntos de usuarios | SÍ |
| `fix:group-129` | Hace todos los pasos automáticamente | SÍ |

### Por qué esto resuelve el problema

1. **Root Cause**: Commit 9042d5f removió compuertas que evitaban evaluación sin datos verificados
2. **Síntoma**: evaluateQuestions se ejecutó pero con lógica determinística incompleta
3. **Solución**: Re-ejecutar evaluadores con QuestionEvaluationService completo
4. **Verificación**: Recalcular puntos basado en opciones correctas actualizadas

---

## ⏱️ Timeline

- **11:00** - Commit 9042d5f deployado (removió hasVerifiedData gates)
- **11:15** - Preguntas Group 129 se re-evaluaron (solo 1 correcta)
- **11:30** - Problema identificado: Evaluadores retornando resultados incompletos
- **11:45** - Herramientas de diagnóstico/reparación creadas
- **NOW** - Ejecutar herramientas para reparar

---

## 📞 Soporte

Si encuentras problemas:
1. Ejecuta `php artisan debug:match-2003` y guarda la salida
2. Ejecuta `php artisan verify:group-data --group=129` 
3. Reporta si los datos de Match 2003 son correctos o no
4. Si Barcelona debería ser correcta pero Match es Atletico vs Tottenham, hay corrupción de datos

---

**LISTO PARA EJECUTAR**: Los cambios están en main (commit bc28226)
