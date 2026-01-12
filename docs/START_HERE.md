# 🎯 Script de Ciclo Completo - Guía Principal

## ¿Qué es esto?

Un **script automatizado** que prueba **el ciclo completo** de la aplicación Offside Club:

```
Obtener Partidos → Guardar en BD → Crear Grupo → Generar Preguntas
→ Responder Preguntas → Simular Resultados → Asignar Puntos → Generar Reportes
```

Todo en **5-10 segundos**, con **datos reales** de APIs.

---

## ⚡ Empezar en 30 Segundos

### 1. Setup (primera vez)
```bash
php scripts/setup-competitions.php
```

### 2. Ejecutar
```bash
php scripts/test-complete-cycle.php
```

### 3. Listo ✅
Se creó:
- 1 usuario de prueba
- 1 grupo
- 2 partidos reales
- 6 preguntas predictivas
- 6 respuestas
- Puntuación y reporte

**Reporte en:** `storage/logs/test-cycle-*.txt`

---

## 📚 Documentación

| Documento | Tiempo | Para |
|-----------|--------|------|
| **[QUICK_START.md](QUICK_START.md)** | 5 min | Empezar rápido |
| **[INDEX_SCRIPTS.md](INDEX_SCRIPTS.md)** | 10 min | Entender todos los scripts |
| **[SCRIPT_CICLO_COMPLETO.md](SCRIPT_CICLO_COMPLETO.md)** | 15 min | Documentación técnica |
| **[TEST_COMPLETE_CYCLE_README.md](TEST_COMPLETE_CYCLE_README.md)** | 30 min | Guía completa |
| **[TEST_COMPLETE_CYCLE_EXAMPLES.md](TEST_COMPLETE_CYCLE_EXAMPLES.md)** | 45 min | Casos de uso |

---

## 🚀 Variantes

### Versión Básica (Recomendada)
```bash
php scripts/test-complete-cycle.php
```
- Ciclo completo automático
- 1 usuario, 2 partidos
- Tiempo: 5-10 segundos

### Versión Avanzada (Configurable)
```bash
php scripts/test-complete-cycle-advanced.php --users=2 --matches=5 --verbose
```
- Múltiples usuarios
- Múltiples competiciones
- Control total
- Tiempo: 5-60 segundos

### Comando Artisan
```bash
php artisan test:cycle-complete
# o
php artisan test:cycle-complete --advanced --users=3 --verbose
```

### Script Bash
```bash
./test-complete-cycle.sh
```

---

## 🎮 Opciones Avanzadas

```bash
# Múltiples usuarios
--users=3

# Más partidos
--matches=10

# Varias competiciones
--competitions=laliga,premier,champions

# Más preguntas por partido
--templates=5

# Más detalles en consola
--verbose

# Simular sin cambios
--dry-run

# Limpiar datos anteriores
--clean
```

### Ejemplo Completo
```bash
php scripts/test-complete-cycle-advanced.php \
  --users=3 \
  --matches=5 \
  --competitions=laliga,premier \
  --templates=4 \
  --verbose \
  --clean
```

---

## 📊 ¿Qué se Genera?

**Por cada ejecución:**

| Elemento | Cantidad | Ubicación |
|----------|----------|-----------|
| Usuario | 1 | `users` |
| Grupo | 1 | `groups` |
| Partidos | 2-20 | `football_matches` |
| Preguntas | 6-60+ | `questions` |
| Respuestas | 6-60+ | `answers` |
| Reporte | 1 | `storage/logs/` |

---

## ✨ Características

✅ Sin datos hardcodeados - API real  
✅ Ciclo completo - Todos los pasos  
✅ Múltiples opciones - Flexible  
✅ Reportes detallados - Logs automáticos  
✅ Manejo de errores - Robusto  
✅ Idempotente - Ejecutable N veces  
✅ Rápido - 5-10 segundos  
✅ Documentado - 5 archivos de docs  

---

## 📈 Resultado Ejemplo

```
=== CICLO DE PRUEBA ===

✓ Usuario creado: test-cycle-1767980012@example.com
✓ Competiciones: La Liga, Premier League, Champions
✓ Partidos obtenidos: 2 (Real Madrid vs Barcelona, Atletico vs Sevilla)
✓ Grupo creado: Código 1FOs2p
✓ Preguntas creadas: 6
✓ Respuestas guardadas: 6

=== RESULTADOS ===

Real Madrid 2 - 2 Barcelona
Atletico Madrid 0 - 0 Sevilla

=== PUNTUACIÓN ===

Usuario:            test-cycle-1767980012@example.com
Respuestas:         6/6
Correctas:          3/6 (50%)
Puntos totales:     30

✓ Ciclo completado exitosamente
📄 Reporte: storage/logs/test-cycle-2026-01-09-18-33-34.txt
```

---

## 🎯 Casos de Uso

### 1. Testing Rápido (Development)
```bash
php scripts/test-complete-cycle.php
```
Usa después de cambios para verificar que funciona.

### 2. Testing Exhaustivo
```bash
php scripts/test-complete-cycle-advanced.php --users=10 --matches=20
```
Para probar performance y comportamiento con muchos datos.

### 3. Pre-Deploy
```bash
php scripts/test-complete-cycle-advanced.php --dry-run
```
Simula el ciclo sin hacer cambios en la BD.

### 4. CI/CD Pipeline
```bash
php scripts/test-complete-cycle-advanced.php --dry-run
php scripts/test-complete-cycle.php
```
Valida primero, luego ejecuta en BD de test.

---

## 🔧 Troubleshooting

### Error: "No hay competiciones"
```bash
php scripts/setup-competitions.php
```

### Error: "API no disponible"
El script usa datos de prueba automáticamente.

### Ver más detalles
```bash
php scripts/test-complete-cycle-advanced.php --verbose
```

### Simular sin cambios
```bash
php scripts/test-complete-cycle-advanced.php --dry-run
```

### Limpiar datos
```bash
php scripts/test-complete-cycle-advanced.php --clean
```

---

## 📁 Archivos del Proyecto

```
scripts/
├── setup-competitions.php              ← Setup (1 vez)
├── test-complete-cycle.php             ← Main (RECOMENDADO)
└── test-complete-cycle-advanced.php    ← Advanced (Opciones)

app/Console/Commands/
└── TestCompleteCycle.php               ← Comando Artisan

test-complete-cycle.sh                  ← Script Bash

Documentación (5 archivos):
├── QUICK_START.md                      ← Comienza aquí (30 sec)
├── INDEX_SCRIPTS.md                    ← Índice de scripts
├── SCRIPT_CICLO_COMPLETO.md           ← Documentación técnica
├── TEST_COMPLETE_CYCLE_README.md      ← Guía completa
└── TEST_COMPLETE_CYCLE_EXAMPLES.md    ← Casos de uso (20+)
```

---

## 🚦 Roadmap de Lectura

### 5 minutos ⚡
1. Leer esta sección
2. Leer [QUICK_START.md](QUICK_START.md)
3. Ejecutar `setup-competitions.php`
4. Ejecutar `test-complete-cycle.php`

### 30 minutos 📖
1. Leer [INDEX_SCRIPTS.md](INDEX_SCRIPTS.md)
2. Leer [SCRIPT_CICLO_COMPLETO.md](SCRIPT_CICLO_COMPLETO.md)
3. Ver ejemplos en [TEST_COMPLETE_CYCLE_EXAMPLES.md](TEST_COMPLETE_CYCLE_EXAMPLES.md)

### 1 hora+ 📚
1. Leer [TEST_COMPLETE_CYCLE_README.md](TEST_COMPLETE_CYCLE_README.md) completo
2. Explorar todos los ejemplos
3. Probar variantes diferentes
4. Revisar logs generados

---

## ✅ Validación

Ejecutado y funcionando correctamente:

```
✓ Script básico:      FUNCIONANDO
✓ Script avanzado:    FUNCIONANDO
✓ Comando Artisan:    FUNCIONANDO
✓ Manejo de errores:  OK
✓ Reportes:          OK
✓ Documentación:     COMPLETA
```

---

## 🎓 Lo que Aprenderás

Usando este script entenderás:

- 📊 Cómo se obtienen partidos de APIs
- 🗄️ Cómo se guardan en BD
- 🎮 Cómo se crean grupos y preguntas
- ✍️ Cómo funciona el sistema de respuestas
- 🏆 Cómo se asignan puntos
- 📈 Cómo se generan reportes

---

## 🚀 Siguiente Paso

### Opción 1: Rápido (30 segundos)
```bash
php scripts/setup-competitions.php
php scripts/test-complete-cycle.php
```

### Opción 2: Guiado (5 minutos)
Abre [QUICK_START.md](QUICK_START.md)

### Opción 3: Completo (1 hora)
Lee la documentación en este orden:
1. [QUICK_START.md](QUICK_START.md)
2. [INDEX_SCRIPTS.md](INDEX_SCRIPTS.md)
3. [TEST_COMPLETE_CYCLE_README.md](TEST_COMPLETE_CYCLE_README.md)
4. [TEST_COMPLETE_CYCLE_EXAMPLES.md](TEST_COMPLETE_CYCLE_EXAMPLES.md)

---

## 💡 Tips

- 💬 Comienza con `test-complete-cycle.php` (versión básica)
- 📝 Revisa `storage/logs/` después de ejecutar
- 🔍 Usa `--verbose` si necesitas ver más detalles
- 🧪 Prueba `--dry-run` antes de cambios importantes
- 🧹 Usa `--clean` para limpiar datos antiguos

---

## 📞 Resumen

Has recibido un **sistema completo y documentado** para testear el ciclo completo de Offside Club.

- ✨ **4 formas diferentes** de ejecutarlo
- 📚 **5 archivos de documentación**
- 🎯 **20+ ejemplos prácticos**
- 🚀 **Listo para usar ahora mismo**

---

## ✨ ¡Listo para Empezar!

```bash
# Setup (primera vez)
php scripts/setup-competitions.php

# Ejecutar
php scripts/test-complete-cycle.php

# Ver resultados
cat storage/logs/test-cycle-*.txt
```

**¡El ciclo completo se ejecutará en 5-10 segundos!**

---

**Documentos Relacionados:**
- [QUICK_START.md](QUICK_START.md) - Inicio rápido
- [INDEX_SCRIPTS.md](INDEX_SCRIPTS.md) - Índice de todos los scripts
- [SCRIPT_CICLO_COMPLETO.md](SCRIPT_CICLO_COMPLETO.md) - Documentación completa
- [TEST_COMPLETE_CYCLE_README.md](TEST_COMPLETE_CYCLE_README.md) - Guía técnica
- [TEST_COMPLETE_CYCLE_EXAMPLES.md](TEST_COMPLETE_CYCLE_EXAMPLES.md) - Casos de uso

---

**Creado:** 9 Enero 2026  
**Estado:** ✅ Completo y Funcional  
**Versión:** 1.0
