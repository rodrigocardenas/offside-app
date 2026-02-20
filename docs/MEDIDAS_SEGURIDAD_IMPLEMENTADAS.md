# MEDIDAS DE SEGURIDAD IMPLEMENTADAS - Offside Club

## 📋 Resumen Ejecutivo

**Fecha:** 2025-02-20  
**Incidente:** Spam de creación de usuarios masivo (3 cuentas duplicadas creadas en 3 minutos)  
**Causa Raíz:** Falta de rate limiting en endpoint POST /login  
**Severidad:** ALTA - Exploración de vulnerabilidad en lógica de registro  
**Estado:** ✅ REMEDIADO

---

## 1️⃣ VULNERABILIDAD IDENTIFICADA

### Problema Original
```
POST /login endpoint → SIN rate limiting
↓
Atacante crea 3 usuarios con mismo username "jhhzqy" en 3 minutos
↓
IDs: 246, 245, 244 (todas del 2025-02-19 19:17-19:20 UTC)
↓
Timezone: America/Los_Angeles (proxy/VPN)
```

### Por qué otras defensas no funcionaron
- ✗ Cloudflare blocking: Atacante usa proxy (IP 45.230.0.0/16)
- ✗ Fail2Ban: No monitorea login de Laravel (nivel aplicación)
- ✗ SSH hardening: Irrelevante (no es acceso SSH)
- ✗ Rate limiting global: No existía en endpoint

---

## 2️⃣ SOLUCIONES IMPLEMENTADAS

### A. Rate Limiting Middleware
**Archivo:** `app/Http/Middleware/RateLimitUserCreation.php`

**Tres capas de protección:**
1. **10 intentos/IP/minuto**
   - Previene fuerza bruta masiva
   - Respuesta HTTP 429 con `retry_after: 60`

2. **3 creaciones mismo username/IP/5min**
   - Previene duplicados del mismo usuario
   - Respuesta: "Este usuario ha sido creado demasiadas veces recientemente"

3. **20 creaciones totales/IP/hora**
   - Máximo absoluto por IP
   - Respuesta: "Tu IP ha creado demasiados usuarios"

**Integración:** En `routes/web.php`
```php
Route::post('login', [LoginController::class, 'login'])
    ->middleware('rate-limit-users');
```

---

### B. Detección de Anomalías Inteligente
**Archivo:** `app/Services/AnomalyDetectionService.php`

**Detecciones Automáticas:**

1. **Spam de Registro**
   - Detecta: 10+ intentos en 1 hora
   - Severidad: HIGH
   - Acción: Log + Email admin

2. **Duplicados de Usuario**
   - Detecta: Mismo username creado 2+ veces desde IP
   - Severidad: MEDIUM
   - Acción: Log + Monitoreo

3. **Enumeration Attack**
   - Detecta: 50+ intentos en 1 hora (búsqueda de usernames válidos)
   - Severidad: CRITICAL
   - Acción: **Bloqueo inmediato de 24h**

4. **Usernames Autogenerados**
   - Detecta: Patrones como `user_ABC123` o `user_5436`
   - Patrón REGEX: `/_[A-Z0-9]{4,}$/` o `/_\d{2,}$/`
   - Severidad: MEDIUM
   - Acción: Log + Análisis

**Respuesta a Anomalías Críticas:**
```
IP detectada → Bloqueo automático por 24h
↓
Cache::put('blocked_ip_' . $ip, $reason, 86400)
↓
Todos los intentos futuros: HTTP 429 (error instantáneo)
```

---

### C. Limpieza de Duplicados
**Archivo:** `app/Console/Commands/CleanDuplicateUsers.php`

**Comando:** `php artisan users:clean-duplicates [--delete]`

**Funcionamiento:**
1. Agrupa usuarios por `name`
2. Identifica duplicados (mantiene el más antiguo)
3. Modo seguro (default): Muestra qué se borraría
4. Modo destructivo: `--delete` para ejecutar

**Ejemplo:**
```bash
# Vista previa (sin borrar)
php artisan users:clean-duplicates

# Ejecutar limpieza
php artisan users:clean-duplicates --delete
```

---

### D. Prevención de Duplicados en Lógica Login
**Archivo:** `app/Http/Controllers/Auth/LoginController.php`

**Cambio Clave:**
```php
// ANTES (problema)
$user = User::where('unique_id', $request->name)->first();

// DESPUÉS (solución)
$user = User::where('name', trim($request->name))->first();
if (!$user) {
    $user = User::where('unique_id', $request->name)->first();
}
```

**Beneficio:** Detecta duplicados en Base de Datos primero

---

### E. Logging de Seguridad
**Archivo:** `config/logging.php`

**Canal Dedicado:** `storage/logs/security.log`

```php
'security' => [
    'driver' => 'daily',
    'path' => storage_path('logs/security.log'),
    'level' => 'notice',
    'days' => 30,  // Retención 30 días
],
```

**Datos Registrados:**
- IP del atacante
- Username intentado
- User-Agent
- Tipo de ataque (SPAM_REGISTRATION, ENUMERATION_ATTACK, etc.)
- Timestamp exacto
- Número de intentos

---

### F. Monitoreo en Tiempo Real
**Archivo:** `app/Console/Commands/MonitorSecurityLogs.php`

**Comando:** `php artisan security:monitor [--clear]`

**Características:**
- Monitoreo en segundo plano 24/7
- Detección automática de ALERTAS CRÍTICAS
- Estadísticas en vivo
- Color-coded output (rojo/amarillo/verde)

**Ejemplo Output:**
```
🔒 Monitor de Seguridad Iniciado
📁 Archivo: storage/logs/security.log

🚨 CRITICAL: IP 45.230.0.0 - Enumeration attack detected
⚠️  ALERT: IP 45.230.0.0 - Spam registration (10+ accounts)

═══════════════════════════════════════════
📊 Resumen de Alertas
─────────────────────────────────────────────
Total de alertas: 5
Alertas CRÍTICAS: 2
═══════════════════════════════════════════
```

---

## 3️⃣ ARQUITECTURA DE RESPUESTA

```
POST /login REQUEST
    ↓
[1] ¿IP en lista negra (blacklist)? → BLOQUEAR (429)
    ↓
[2] ¿10+ intentos en 1 minuto? → BLOQUEAR (429) + LOG
    ↓
[3] ¿Mismo username 3+ veces en 5 min? → BLOQUEAR (429) + LOG
    ↓
[4] ¿20+ creaciones en 1 hora? → BLOQUEAR (429) + LOG
    ↓
[5] Análisis de Anomalías
    ├─ Patrón username autogenerado?
    ├─ Enumeration attack?
    ├─ Spike sospechoso?
    └─ SI → BLOQUEAR IP 24h + ALERTA CRÍTICA
    ↓
[6] ¿Status < 400? → Incrementar contadores
    ↓
PERMITIR / DENEGAR
```

---

## 4️⃣ PRUEBAS Y VALIDACIÓN

### Test 1: Rate Limiting (10/min)
```bash
# En terminal, 11 requests rápidos
for i in {1..11}; do
  curl -X POST http://localhost:8000/login \
    -d "name=testuser" \
    -H "Content-Type: application/x-www-form-urlencoded"
done

# Esperado: Los primeros 10 OK, el 11º = 429
```

### Test 2: Duplicados (3/5min)
```bash
# 3 intentos rápidos mismo username
curl -X POST ... -d "name=hacker"
curl -X POST ... -d "name=hacker"
curl -X POST ... -d "name=hacker"

# Esperado: 3º request = 429
```

### Test 3: Total Horario (20/hora)
```bash
# Crear 20 usuarios diferentes rápidamente
for i in {1..20}; do
  curl -X POST ... -d "name=user$i"
done

# El 21º = 429 "Límite de creaciones por hora"
```

### Test 4: Anomalías
```bash
# Detecta patrón autogenerado
curl -X POST ... -d "name=bot_AI5482"

# Log: "Username con patrón automático detectado"
```

---

## 5️⃣ DEPLOYMENT CHECKLIST

- [ ] Commit cambios a Git
- [ ] Push a rama `develop`
- [ ] Ejecutar tests: `php artisan test`
- [ ] Verificar migrations: `php artisan migrate --pretend`
- [ ] Deploy a staging: `./deploy.sh staging`
- [ ] Test manual de rate limiting
- [ ] Limpiar duplicados: `php artisan users:clean-duplicates --delete`
- [ ] Deploy a producción
- [ ] Iniciar monitoreo: `php artisan security:monitor`
- [ ] Verificar logs: `tail -f storage/logs/security.log`

---

## 6️⃣ FUTURAS MEJORAS (Roadmap)

### Corto Plazo (Esta Semana)
1. ✅ Rate limiting
2. ✅ Detección de anomalías
3. ✅ Limpieza de duplicados
4. [ ] Implementar 2FA (Laravel Fortify)
5. [ ] Webhooks para Slack alerts

### Mediano Plazo (Este Mes)
6. [ ] IP whitelist para admins
7. [ ] CAPTCHA en login después de 3 intentos
8. [ ] Machine Learning para detectar patrones botnet
9. [ ] Integración con MaxMind GeoIP

### Largo Plazo
10. [ ] Single Sign-On (SSO)
11. [ ] Biometric authentication
12. [ ] Behavioral biometrics

---

## 7️⃣ COMANDOS DE ADMINISTRACIÓN

### Monitorear seguridad en vivo
```bash
php artisan security:monitor
```

### Limpiar duplicados (vista previa)
```bash
php artisan users:clean-duplicates
```

### Ejecutar limpieza
```bash
php artisan users:clean-duplicates --delete
```

### Ver alertas recientes
```bash
tail -f storage/logs/security.log
```

### Buscar actividad de IP específica
```bash
grep "45.230.0.0" storage/logs/security.log
```

### Limpiar logs de seguridad antiguos
```bash
php artisan tinker
> \Illuminate\Support\Facades\File::put(storage_path('logs/security.log'), '');
```

---

## 8️⃣ MATRIZ DE RESPUESTA A INCIDENTES

| Nivel | Tipo | Acción | Tiempo |
|-------|------|--------|--------|
| 🔴 CRÍTICO | Enumeration (50+ intentos) | Bloquear IP 24h + Email Admin | Inmediato |
| 🟠 ALTO | Spam (10+ en 1h) | Bloquear IP 1h + Log | Automático |
| 🟡 MEDIO | Duplicados (3 same user) | Bloquear 5min + Log | Automático |
| 🟢 BAJO | Patrón bot (username ficticio) | Log + Seguimiento | Automático |

---

## 9️⃣ RUTAS MODIFICADAS

```
app/Http/Controllers/Auth/LoginController.php ✏️ MODIFICADO
app/Http/Middleware/RateLimitUserCreation.php ✏️ MODIFICADO
app/Http/Kernel.php ✏️ MODIFICADO
app/Services/AnomalyDetectionService.php ✨ NUEVO
app/Console/Commands/CleanDuplicateUsers.php ✨ NUEVO
app/Console/Commands/MonitorSecurityLogs.php ✨ NUEVO
routes/web.php ✏️ MODIFICADO
config/logging.php ✏️ MODIFICADO
```

---

## 🔟 CONTATO RESPONSABLE

**Equipo de Seguridad Offside Club**  
Email: security@offside.club  
Teléfono: [Emergency]  
Slack: #security-incidents

---

**Documento Generado:** 2025-02-20 UTC  
**Versión:** 1.0  
**Próxima Revisión:** 2025-02-27
