# ⚡ QUICK START - API Football PRO

## En 2 minutos: Poner el sistema en funcionamiento

### Terminal 1: Ejecutar Queue Worker
```bash
cd /c/laragon/www/offsideclub
php artisan queue:work
```

**Output esperado:**
```
INFO  Processing jobs from the [default] queue.
(esperará infinitamente por jobs)
```

### Terminal 2: Ver Logs
```bash
cd /c/laragon/www/offsideclub
tail -f storage/logs/laravel.log | grep -E "ACTUALIZADO|ERROR|Status"
```

### Terminal 3: Test (Opcional)
```bash
cd /c/laragon/www/offsideclub
php test-api-pro.php  # Ver estado de API
php test-complete-pipeline.php  # Test completo
```

---

## Ciclo Automático (Cada Hora)

```
00:00 → UpdateFinishedMatchesJob inicia
  ↓
Busca partidos sin actualizar (últimas 72h)
  ↓
Obtiene resultados de API Football PRO
  ↓
Guarda en BD: status + goles
  ↓
00:05 → VerifyFinishedMatchesHourlyJob verifica
```

---

## Comandos Útiles

| Comando | Qué hace |
|---------|----------|
| `php artisan queue:work` | ⏪ Ejecutar jobs (infinito) |
| `php artisan queue:work --timeout=600` | ⏪ Con timeout de 10min |
| `php artisan queue:flush` | 🗑️ Limpiar queue |
| `php artisan queue:failed` | ❌ Ver jobs fallidos |
| `php artisan queue:retry all` | 🔄 Reintentar fallidos |
| `php test-api-pro.php` | ✅ Verificar API |
| `php test-complete-pipeline.php` | 📊 Test end-to-end |

---

## Verificación Rápida

```bash
# ¿API Football está conectada?
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$r = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders(['x-apisports-key' => config('services.football.key')])->get('https://v3.football.api-sports.io/status');
echo 'Status: ' . \$r->status() . \"\n\";
echo 'Plan: ' . \$r->json()['response']['subscription']['plan'] . \"\n\";
echo 'Active: ' . (\$r->json()['response']['subscription']['active'] ? 'YES' : 'NO') . \"\n\";
echo 'Requests today: ' . \$r->json()['response']['requests']['current'] . '/' . \$r->json()['response']['requests']['limit_day'] . \"\n\";
"
```

---

## ¿Qué cambió?

| Aspecto | Antes | Después |
|--------|-------|---------|
| **API** | RapidAPI (proxy) | API Football Oficial |
| **Endpoint** | `api-football-v1.p.rapidapi.com` | `v3.football.api-sports.io` |
| **Plan** | Free (100/día) | Pro (7500/día) |
| **Headers** | 2 (X-RapidAPI-Key + Host) | 1 (x-apisports-key) |
| **Status** | Rate limited | Funcionando perfectamente ✅ |

---

## 🎯 Próxima Ejecución

El sistema se ejecutará **automáticamente cada hora** si tienes el queue worker corriendo:
- `:00` → Obtiene resultados
- `:05` → Verifica eventos

Para verlo en acción:
```bash
# 1. Ejecutar queue worker
php artisan queue:work

# 2. Esperar a las :00 de la próxima hora
# 3. Ver logs en la otra terminal
```

---

## 📊 Recursos

- **Documentación completa:** [API_FOOTBALL_DEPLOYMENT.md](API_FOOTBALL_DEPLOYMENT.md)
- **Setup de Queue:** [QUEUE_WORKER_SETUP.md](QUEUE_WORKER_SETUP.md)
- **Tests:** `php test-api-pro.php`, `php test-complete-pipeline.php`

---

**Status:** ✅ PRODUCTION READY  
**Última actualización:** 23-01-2026  
**Plan:** PRO (7500 requests/día)

