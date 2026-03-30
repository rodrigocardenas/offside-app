# Troubleshooting: Gemini Timeout

## Problema
Las llamadas a la API de Gemini están dando timeout (esperan más de 60 segundos).

## Causas Principales

### 1. **API de Gemini está lenta o saturada**
- Google Gemini API puede tener picos de latencia
- Rate limiting puede causar retrasos

### 2. **Grounding (web search) es lento**
- `GEMINI_GROUNDING_ENABLED=true` en `.env` hace búsquedas web
- Las búsquedas web agregan 10-30 segundos al tiempo de respuesta
- Es necesario para verificar datos reales, pero hace más lento

### 3. **Problemas de red**
- Latencia alta entre el servidor y la API de Gemini
- Conexión inestable

### 4. **Rate limit acumulado**
- Después de muchas llamadas rápidas, Gemini empieza a rechazar con 429
- Esto causa intentos de reintento que suman tiempo

## Soluciones

### Solución 1: Deshabilitar Grounding para comandos
```bash
php artisan questions:repair --match-id=296 --no-grounding
```
- Elimina búsquedas web, hace más rápido (~5-10 segundos por pregunta)
- Menos preciso pero funciona para preguntas basadas en eventos locales

### Solución 2: Aumentar timeout en .env
```bash
GEMINI_TIMEOUT=90
```
- Esperará hasta 90 segundos en lugar de 60
- Útil si la red es lenta pero consistente

### Solución 3: Reducir max_retries
```bash
GEMINI_MAX_RETRIES=2
```
- Por defecto intenta 3 veces con delays de 90 segundos cada una
- Reducir a 2 intentos = máximo 180 segundos en lugar de 270

### Solución 4: Usar --limit para procesar pocas preguntas
```bash
php artisan questions:repair --match-id=296 --limit=10 --no-grounding
```
- Procesa solo 10 preguntas primero
- Útil para testing y ver si es problema sistemático

### Solución 5: Modo non-blocking (automático)
```bash
php artisan questions:repair --match-id=296
```
- El comando NUNCA se bloquea esperando Gemini
- Si Gemini falla, salta las preguntas y continúa
- Es seguro ejecutar durante horas

## Configuración Recomendada para Desarrollo

```bash
# .env
GEMINI_TIMEOUT=90
GEMINI_MAX_RETRIES=2
GEMINI_GROUNDING_ENABLED=false  # Solo si tienes problemas recurrentes
```

## Monitoreo

### Ver logs de Gemini
```bash
grep -i "gemini\|timeout\|429" storage/logs/laravel.log
```

### Ver qué preguntas fallan
```bash
php artisan questions:repair --match-id=296 --show-details 2>&1 | grep "❌\|⚠️"
```

## Información Adicional

- **API Free Tier**: ~60 llamadas/minuto
- **Timeout configurado**: 60 segundos (aumentado a 90 en config/gemini.php)
- **Rate limit**: 429 error = esperar 90 segundos antes de reintentar
- **Cache**: Los resultados se cachean por 48 horas por defecto

## Si los problemas persisten

1. Verificar que `GEMINI_API_KEY` es válida en `.env`
2. Probar la API directamente:
   ```bash
   php artisan tinker
   > \App\Services\GeminiService::class
   > (new \App\Services\GeminiService())->callGemini('Di hola', false)
   ```
3. Revisar estado de Google Gemini API en console.cloud.google.com
4. Reducir carga del sistema (CPU, memoria)
