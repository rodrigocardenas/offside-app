# ⚡ Quick Start - Script de Ciclo Completo

## 30 segundos para empezar

### 1. Setup (Hazlo una sola vez)
```bash
php scripts/setup-competitions.php
```

### 2. Ejecutar
```bash
php scripts/test-complete-cycle.php
```

### ✅ Done!
Espera 5-10 segundos y listo. Se creará:
- ✓ 1 usuario de prueba
- ✓ 1 grupo
- ✓ 2 partidos
- ✓ 6 preguntas
- ✓ 6 respuestas
- ✓ Puntuación y reporte

---

## 📖 Versión Expandida (Opciones)

```bash
# Con múltiples usuarios
php scripts/test-complete-cycle-advanced.php --users=3

# Con más partidos
php scripts/test-complete-cycle-advanced.php --matches=5

# Con varias competiciones
php scripts/test-complete-cycle-advanced.php --competitions=laliga,premier,champions

# Todo junto
php scripts/test-complete-cycle-advanced.php --users=2 --matches=3 --competitions=laliga,premier --verbose

# Limpiar datos anteriores
php scripts/test-complete-cycle-advanced.php --clean

# Simular sin cambios
php scripts/test-complete-cycle-advanced.php --dry-run
```

---

## 🎯 Comandos Artisan

```bash
# Versión básica
php artisan test:cycle-complete

# Versión avanzada
php artisan test:cycle-complete --advanced --users=2

# Con todas las opciones
php artisan test:cycle-complete --advanced \
  --users=3 \
  --matches=5 \
  --competitions=laliga,premier \
  --verbose
```

---

## 📊 Qué Sucede

```
1. Obtiene partidos reales (La Liga, Premier, Champions)
2. Los guarda en BD
3. Crea un grupo
4. Genera preguntas predictivas
5. Responde las preguntas
6. Simula resultados
7. Verifica respuestas y asigna puntos
8. Genera reporte

⏱️ Tiempo total: 5-30 segundos
📁 Reporte guardado en: storage/logs/test-cycle-*.txt
```

---

## 🔍 Ver Resultados

```bash
# Ver último reporte
cat storage/logs/test-cycle-*.txt

# Ver en la app
# 1. Accede a: http://localhost/offsideclub
# 2. Email: test-cycle-XXXXXX@example.com
# 3. Contraseña: password123
# 4. Mira el grupo y preguntas creadas
```

---

## 🧹 Limpiar (Opcional)

```bash
# Opción 1: Automática
php scripts/test-complete-cycle-advanced.php --clean

# Opción 2: Manual
php artisan tinker
>>> User::where('email', 'like', 'test-cycle-%')->each(function($u) { $u->delete(); });
>>> exit()
```

---

## ❓ FAQ Rápidas

**P: ¿Funciona si la API está caída?**  
R: Sí, usa datos de prueba locales automáticamente.

**P: ¿Puedo ejecutarlo múltiples veces?**  
R: Sí, cada vez crea datos nuevos (no interfiere).

**P: ¿Dónde veo qué se generó?**  
R: En `storage/logs/` y en la BD.

**P: ¿Cuánto tiempo tarda?**  
R: 5-10 segundos (versión básica), 15-30 segundos (avanzada).

---

## 📚 Más Información

- Guía completa: [TEST_COMPLETE_CYCLE_README.md](TEST_COMPLETE_CYCLE_README.md)
- Ejemplos: [TEST_COMPLETE_CYCLE_EXAMPLES.md](TEST_COMPLETE_CYCLE_EXAMPLES.md)
- Documentación: [SCRIPT_CICLO_COMPLETO.md](SCRIPT_CICLO_COMPLETO.md)

---

**¡Listo para testear! 🚀**
