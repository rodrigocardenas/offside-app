# 📑 Índice de Scripts y Documentación

## 🚀 Scripts de Prueba

### 1. Setup (Ejecutar primero, una sola vez)
**Archivo:** `scripts/setup-competitions.php`
```bash
php scripts/setup-competitions.php
```
**Qué hace:** Carga 6 competiciones en la BD (La Liga, Premier, Champions, etc)
**Tiempo:** <1 segundo

---

### 2. Script Básico (RECOMENDADO)
**Archivo:** `scripts/test-complete-cycle.php`
```bash
php scripts/test-complete-cycle.php
```
**Qué hace:** 
- Ciclo completo en una ejecución
- 1 usuario, 2 partidos, 6 preguntas
- Obtiene datos reales desde APIs

**Tiempo:** 5-10 segundos
**Ideal para:** Testing rápido durante desarrollo

---

### 3. Script Avanzado (Con Opciones)
**Archivo:** `scripts/test-complete-cycle-advanced.php`
```bash
php scripts/test-complete-cycle-advanced.php [opciones]
```
**Opciones disponibles:**
- `--users=N` - Número de usuarios (default: 1)
- `--matches=N` - Número de partidos (default: 2)
- `--competitions=laliga,premier` - Competiciones (default: laliga)
- `--templates=N` - Plantillas de preguntas (default: 3)
- `--verbose` - Mostrar más detalles
- `--dry-run` - Simular sin cambios
- `--clean` - Limpiar datos anteriores

**Ejemplos:**
```bash
# Test exhaustivo
php scripts/test-complete-cycle-advanced.php --users=3 --matches=5 --verbose

# Validación sin cambios
php scripts/test-complete-cycle-advanced.php --dry-run

# Limpiar y recrear
php scripts/test-complete-cycle-advanced.php --clean
```

**Tiempo:** 5-60 segundos (depende de opciones)
**Ideal para:** Testing exhaustivo, CI/CD, validación

---

### 4. Comando Artisan
**Archivo:** `app/Console/Commands/TestCompleteCycle.php`
```bash
php artisan test:cycle-complete [opciones]
```
**Ejemplos:**
```bash
# Versión básica
php artisan test:cycle-complete

# Versión avanzada
php artisan test:cycle-complete --advanced --users=2 --verbose

# Con opciones completas
php artisan test:cycle-complete --advanced \
  --users=3 \
  --matches=5 \
  --competitions=laliga,premier \
  --verbose
```

**Ideal para:** Integración con workflows Artisan

---

### 5. Script Bash
**Archivo:** `test-complete-cycle.sh`
```bash
chmod +x test-complete-cycle.sh
./test-complete-cycle.sh
```
**Ideal para:** Automatización en shell scripts, cron jobs

---

## 📚 Documentación

### QUICK START ⚡ (Comienza aquí - 30 segundos)
**Archivo:** `QUICK_START.md`
- Inicio rápido en 3 pasos
- Comandos básicos
- FAQ rápidas

**Leer si:** Quieres empezar ahora mismo

---

### SCRIPT CICLO COMPLETO 📋 (Visión General)
**Archivo:** `SCRIPT_CICLO_COMPLETO.md`
- Resumen ejecutivo
- Características principales
- Workflows recomendados
- Troubleshooting

**Leer si:** Quieres entender qué hace el script

---

### TEST COMPLETE CYCLE README 📖 (Guía Detallada)
**Archivo:** `TEST_COMPLETE_CYCLE_README.md`
- Requisitos previos
- Instalación
- Flujo de ejecución detallado (11 pasos)
- Interpretación de resultados
- Personalización
- Estructura de BD

**Leer si:** Quieres conocer todos los detalles

---

### TEST COMPLETE CYCLE EXAMPLES 🎯 (Casos de Uso)
**Archivo:** `TEST_COMPLETE_CYCLE_EXAMPLES.md`
- 20+ ejemplos prácticos
- Workflows de integración
- Monitoreo y debugging
- Tips y trucos
- Preguntas frecuentes

**Leer si:** Necesitas ejemplos específicos

---

### RESUMEN SCRIPT CICLO COMPLETO ✅ (Este Archivo)
**Archivo:** `RESUMEN_SCRIPT_CICLO_COMPLETO.md`
- Resumen de todo lo creado
- Validación de funcionamiento
- Guía rápida de uso

**Leer si:** Quieres un resumen ejecutivo

---

## 🗂️ Estructura de Archivos

```
offsideclub/
│
├── scripts/
│   ├── setup-competitions.php              ← START HERE (Setup)
│   ├── test-complete-cycle.php             ← MAIN (Versión básica)
│   └── test-complete-cycle-advanced.php    ← ADVANCED (Con opciones)
│
├── app/Console/Commands/
│   └── TestCompleteCycle.php               ← Comando Artisan
│
├── test-complete-cycle.sh                  ← Script Bash
│
├── QUICK_START.md                          ← 🚀 COMIENZA AQUÍ (30 seg)
├── SCRIPT_CICLO_COMPLETO.md               ← 📋 Documentación técnica
├── TEST_COMPLETE_CYCLE_README.md          ← 📖 Guía completa
├── TEST_COMPLETE_CYCLE_EXAMPLES.md        ← 🎯 Ejemplos prácticos
└── RESUMEN_SCRIPT_CICLO_COMPLETO.md       ← ✅ Resumen ejecutivo
```

---

## 🎯 Flujo de Uso Recomendado

### Primera Vez (Setup)
```
1. Lee: QUICK_START.md (2 minutos)
2. Ejecuta: php scripts/setup-competitions.php (< 1 segundo)
3. Ejecuta: php scripts/test-complete-cycle.php (5-10 segundos)
4. ✅ Listo
```

### Uso Regular (Development)
```
1. Ejecuta: php scripts/test-complete-cycle.php
2. Revisa: storage/logs/test-cycle-*.txt
3. ✅ Continúa desarrollando
```

### Testing Exhaustivo
```
1. Lee: TEST_COMPLETE_CYCLE_EXAMPLES.md
2. Ejecuta: php scripts/test-complete-cycle-advanced.php --users=5 --matches=10 --verbose
3. Revisa: logs y BD
4. ✅ Valida que todo funciona
```

### Para CI/CD
```
1. Lee: TEST_COMPLETE_CYCLE_EXAMPLES.md (sección "Con CI/CD Pipeline")
2. Ejecuta: php scripts/test-complete-cycle-advanced.php --dry-run
3. Si OK: php scripts/test-complete-cycle.php
4. ✅ Deploy
```

---

## ⏱️ Tiempos de Ejecución

| Comando | Tiempo | Datos Generados |
|---------|--------|-----------------|
| `setup-competitions.php` | <1 sec | 6 competiciones |
| `test-complete-cycle.php` | 5-10 sec | 1 usuario, 2 partidos, 6 preguntas |
| `--advanced --users=1 --matches=2` | 5-10 sec | 1 usuario, 2 partidos, 6 preguntas |
| `--advanced --users=3 --matches=5` | 15-30 sec | 3 usuarios, 5 partidos, 45 preguntas |
| `--advanced --users=10 --matches=20` | 60-120 sec | 10 usuarios, 20 partidos, 600 preguntas |
| `--dry-run` | 2-5 sec | Sin cambios (solo simulación) |

---

## 🔍 Validación

✅ **Probado y Funcional**

```
✓ Script básico: FUNCIONANDO
✓ Script avanzado: FUNCIONANDO  
✓ Comando Artisan: FUNCIONANDO
✓ Manejo de errores: OK
✓ Reportes: OK
✓ Documentación: COMPLETA
```

---

## 📞 Troubleshooting Rápido

| Problema | Solución |
|----------|----------|
| "No hay competiciones" | `php scripts/setup-competitions.php` |
| "API no responde" | Script usa datos de prueba automáticamente |
| "Quiero ver más detalles" | Agrega flag `--verbose` |
| "Quiero simular sin cambios" | Usa flag `--dry-run` |
| "Quiero limpiar datos antiguos" | Usa flag `--clean` |
| "Necesito múltiples usuarios" | `--users=N` |

---

## 🎓 Roadmap de Lectura

### 5 minutos
- [ ] Leer QUICK_START.md
- [ ] Ejecutar setup-competitions.php
- [ ] Ejecutar test-complete-cycle.php

### 30 minutos
- [ ] Leer SCRIPT_CICLO_COMPLETO.md
- [ ] Revisar TEST_COMPLETE_CYCLE_README.md
- [ ] Ver algunos ejemplos de TEST_COMPLETE_CYCLE_EXAMPLES.md

### 1 hora
- [ ] Leer toda la documentación
- [ ] Probar múltiples variantes de script
- [ ] Explorar storage/logs/
- [ ] Revisar datos en BD

---

## 🚀 Próximos Pasos

1. **Comienza:**
   ```bash
   php scripts/setup-competitions.php
   php scripts/test-complete-cycle.php
   ```

2. **Personaliza:** (si necesitas)
   ```bash
   php scripts/test-complete-cycle-advanced.php --users=2 --matches=5
   ```

3. **Integra:** (si lo usas en CI/CD)
   ```bash
   # En tu pipeline
   php scripts/test-complete-cycle-advanced.php --dry-run
   ```

4. **Explora:** Revisa la documentación según necesites

---

## 📌 Resumen de Características

✨ **Completo** - Prueba todo el ciclo  
🔄 **Datos reales** - De Football-Data.org  
⚙️ **Flexible** - 6 opciones configurables  
📊 **Reportes** - Logs detallados automáticos  
🛡️ **Robusto** - Manejo completo de errores  
🚀 **Rápido** - 5-10 segundos para ciclo básico  
📚 **Documentado** - 4 archivos de documentación  
🧪 **Probado** - 100% funcional  

---

## ✅ Checklist Final

- [x] Scripts creados y funcionales
- [x] Documentación completa
- [x] Ejemplos prácticos
- [x] Validado en BD real
- [x] Listo para usar de inmediato

---

**¡Estás listo para empezar! 🎉**

**Siguiente paso:** Abre `QUICK_START.md` o ejecuta:
```bash
php scripts/setup-competitions.php && php scripts/test-complete-cycle.php
```

---

**Creado:** 9 Enero 2026  
**Estado:** ✅ Completo  
**Última actualización:** 9 Enero 2026
