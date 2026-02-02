# 📊 QUICK REFERENCE - Force Verify Questions Command

## ✅ Lo Que Se Resolvió

| Problema | Solución | Estado |
|----------|----------|--------|
| ❌ Preguntas no se verificaban | ✅ Se incluyó `'Finished'` status en todos los jobs | ✅ FIXED |
| ❌ Error de serialización en producción | ✅ Se removieron descripciones de signature | ✅ FIXED |
| ❌ No podía re-verificar preguntas | ✅ Agregado flag `--re-verify` | ✅ NEW |

---

## 🚀 Quick Commands

### Diagnóstico
```bash
# Ver estado actual
php artisan app:force-verify-questions --dry-run

# Ver qué se re-verificaría
php artisan app:force-verify-questions --re-verify --dry-run
```

### Ejecución
```bash
# Verificar preguntas pendientes
php artisan app:force-verify-questions

# Re-verificar todas las preguntas del último mes
php artisan app:force-verify-questions --re-verify --days=30

# Re-verificar un match específico
php artisan app:force-verify-questions --match-id=445 --re-verify
```

---

## 📋 Parámetros Disponibles

```
--days=N       Cuántos días hacia atrás (default: 30)
--limit=N      Máximo matches (default: 100)
--match-id=ID  Match específico (omite otros filtros)
--re-verify    Re-verifica preguntas ya verificadas
--dry-run      Solo previsualizar (no ejecuta)
```

---

## 🔍 Casos de Uso

**1. Preguntas antiguas no se verificaron**
```bash
php artisan app:force-verify-questions --days=90 --limit=200
```

**2. Después de mejorar algoritmos de evaluación**
```bash
php artisan app:force-verify-questions --re-verify --days=30
```

**3. Debugging de match específico**
```bash
php artisan app:force-verify-questions --match-id=445 --re-verify --dry-run
```

**4. Backfill completo (¡cuidado!)**
```bash
# PRIMERO: Ver qué se haría
php artisan app:force-verify-questions --re-verify --days=365 --dry-run

# DESPUÉS: Ejecutar (si looks good)
php artisan app:force-verify-questions --re-verify --days=365 --limit=500
```

---

## 📊 Estado Actual

```
✅ Matches finished (30 días):     85
✅ Preguntas verificadas:         1,203
❌ Preguntas pendientes:          15 (<2%)
🔄 Listos para re-verificar:      13

Última verificación:  2026-01-24 02:35:33
```

---

## 📚 Documentación

- **[FORCE_VERIFY_GUIDE.md](FORCE_VERIFY_GUIDE.md)** - Guía completa
- **[RESOLUTION_SUMMARY.md](RESOLUTION_SUMMARY.md)** - Detalles técnicos
- **[DEMO_FORCE_VERIFY.sh](DEMO_FORCE_VERIFY.sh)** - Ejemplos de ejecución

---

## ⚠️ Importante

> ⚠️ **Re-verify** reseta los puntos.  
> Los usuarios **perderán/RECIBIRÁN** puntos basado en la nueva verificación.

> 💡 Usa **--dry-run** primero para ver qué pasará sin ejecutar.

> 🔌 Asegúrate que queue worker esté corriendo:
> ```bash
> php artisan queue:work
> ```

---

**Last Updated:** 2026-02-02  
**Status:** ✅ Production Ready
