## 🚀 GUÍA: Obtener Acceso a Scores en Vivo con API Football

El sistema está configurado para usar **API Football (RapidAPI)** como prioritario, pero necesitas un plan de pago para acceso en vivo.

---

## 📊 Comparativa de Opciones

### OPCIÓN 1: API Football (RapidAPI) - 🏆 RECOMENDADO

**Suscripción:**
- URL: https://rapidapi.com/api-sports/api/api-football
- Plan: **Sports API (Premium)** - $9.99/mes
- Características:
  - ✅ Scores en VIVO (en tiempo real)
  - ✅ Eventos detallados (goles, tarjetas, sustituciones)
  - ✅ Estadísticas del partido
  - ✅ +300 ligas/competiciones
  - ✅ Histórico completo

**Configuración en tu app:**
```
FOOTBALL_API_KEY=tu_rapidapi_key
```
(Ya está en tu `.env`)

**Cómo suscribirse:**
1. Ve a: https://rapidapi.com/api-sports/api/api-football
2. Click en "Subscribe" 
3. Selecciona "Premium - $9.99/month"
4. Completa pago
5. Copia tu API Key de RapidAPI
6. Pega en `.env` como `FOOTBALL_API_KEY=...`

---

### OPCIÓN 2: Football-Data.io

**Suscripción:**
- URL: https://www.football-data.org
- Plan: **Free** - 10 requests/día (muy limitado)
- Plan: **Personal** - €1/mes - 10k requests/día
- Características:
  - ✅ Scores disponibles después del partido
  - ✅ +1000 ligas/competiciones
  - ❌ No es en tiempo real

**Configuración:**
```
FOOTBALL_DATA_API_KEY=tu_football_data_key
```

---

### OPCIÓN 3: Mezcla (Recomendado para ahorro)

Usa **API Football** para partidos principales (ligas top) y **Football-Data.io** como fallback:

```php
// En ProcessMatchBatchJob:
1️⃣ Intenta API Football (si es suscripción pagada)
2️⃣ Intenta Football-Data.io (si está configurado)
3️⃣ Fallback a Gemini (web search)
```

---

## 🔧 Pasos para Suscribirse a API Football

### Paso 1: Crear cuenta en RapidAPI
1. Ir a https://rapidapi.com
2. Click "Sign Up"
3. Registrarse con email o GitHub
4. Verificar email

### Paso 2: Encontrar la API
1. Buscar "API Football" en RapidAPI
2. O ir directamente: https://rapidapi.com/api-sports/api/api-football

### Paso 3: Suscribirse al plan
1. Click en "Premium - $9.99/month"
2. Revisar límites:
   - 500 requests/día
   - 10 requests/segundo
3. Agregar método de pago
4. Confirmar suscripción

### Paso 4: Obtener API Key
1. Una vez suscrito, ir a "Code Snippets" o "API Requests"
2. Copiar el header: `X-RapidAPI-Key`
3. Ese valor es tu key

### Paso 5: Configurar en tu app
```bash
# En .env
FOOTBALL_API_KEY=tu_api_key_aqui
```

### Paso 6: Reiniciar queue
```bash
php artisan queue:restart
```

---

## 💰 Costo-Beneficio

| API | Precio | Scores Vivos | Eventos | Ligas | Recomendación |
|-----|--------|--------------|---------|-------|---|
| **API Football** | $9.99/mes | ✅ Sí | ✅ Sí | 300+ | Para producción |
| **Football-Data** | €1-10/mes | ❌ No | ✅ Sí | 1000+ | Backup económico |
| **Gemini** | Gratis* | ✅ Sí (web search) | ❌ No | Todas | Fallback |

*Gemini: 20 requests/día en free tier, pero con grounding (web search)

---

## ✅ Verificación Post-Suscripción

Una vez tengas la suscripción activa:

```bash
# Test rápido de API Football
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$service = app(App\Services\FootballService::class);
\$result = \$service->updateMatchFromApi(451);

if (\$result) {
    echo '✅ API Football funciona correctamente';
} else {
    echo '❌ API Football aún sin datos o no suscrito';
}
"
```

---

## 🎯 Plan de Acción

**Recomendación:**

1. **HOY:** Suscribirse a API Football Premium ($9.99/mes) - 10 minutos
2. **MAÑANA:** El job funcionará automáticamente con scores en vivo
3. **OPCIONAL:** Agregar Football-Data.io como fallback

**Flujo será:**
```
UpdateFinishedMatchesJob (cada hora)
    ↓
ProcessMatchBatchJob (en queue)
    ├─→ 1️⃣ API Football (datos en vivo) ← PRIORITARIO
    ├─→ 2️⃣ Gemini + web search (si falla #1)
    └─→ ❌ NO ACTUALIZA si ambas fallan
```

---

## 📞 Soporte

Si tienes problemas:

1. **API Key inválida?** → Verifica en https://rapidapi.com/api-sports/api/api-football/keys
2. **Suscripción expirada?** → Renuévala desde tu dashboard de RapidAPI
3. **Límite alcanzado?** → Espera a mañana o sube a plan superior
4. **¿Aún sin datos?** → El fixture puede no estar disponible en la API

---

**¿Listo para suscribirse?** Avísame cuando tengas el API Key y verificamos que funcione. 🚀
