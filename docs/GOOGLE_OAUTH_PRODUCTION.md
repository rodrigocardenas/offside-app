# 🚀 Google OAuth - Configuración Producción

## 📋 Checklist Pre-Deploy

### Fase 1: Google Cloud Console (Producción)

- [ ] Acceder a https://console.cloud.google.com/
- [ ] Ir a **APIs & Services** → **Credentials**
- [ ] Editar OAuth Client ID (Offside Club Web)
- [ ] Agregar URLs de producción:

**Authorized JavaScript origins:**
```
https://app.offsideclub.es
http://offsideclub.test (local para testing)
```

**Authorized redirect URIs:**
```
https://app.offsideclub.es/auth/google/callback
http://offsideclub.test/auth/google/callback (local para testing)
```

- [ ] Click **SAVE** y esperar 2-5 minutos

### Fase 2: Configuración de Servidor (RDS)

**En archivos `.env` de producción:**

```env
# Google OAuth - Producción
# Get credentials from: https://console.cloud.google.com/apis/credentials
GOOGLE_CLIENT_ID=your_google_client_id_here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_CALLBACK_URL=https://app.offsideclub.es/auth/google/callback
```

**Variables de sesión:**
```env
SESSION_DRIVER=cookie  # O 'database' si prefieres persistencia en RDS
SESSION_LIFETIME=12000000
SESSION_SAME_SITE=lax  # Para OAuth en diferente dominio
```

### Fase 3: Verificaciones Pre-Deploy

#### 1️⃣ Tests en staging
```bash
# Asegúrate de que tests pasen
php artisan test

# Ejecutar grupo de tests críticos
php artisan test --group=deploy
```

#### 2️⃣ Verificar rutas
```bash
php artisan route:list | grep auth
```

#### 3️⃣ Cache de configuración
```bash
php artisan config:cache
php artisan route:cache
```

### Fase 4: Deployment

**Usando script de deploy:**
```bash
bash scripts/deploy.sh
```

El script automáticamente:
- ✅ Valida estar en rama `main`
- ✅ Ejecuta tests (`php artisan test --group=deploy`)
- ✅ Compila assets (`npm run build`)
- ✅ Replica cambios vía SSH
- ✅ Ejecuta migraciones
- ✅ Recompila configuración

### Fase 5: Post-Deploy Verification

```bash
# Conectar al servidor de producción
ssh ubuntu@ec2-xxx.compute-1.amazonaws.com

# Verificar que Google OAuth está configurado
php artisan tinker
>>> config('services.google')
# Debe devolver: ['client_id' => '...', 'client_secret' => '...', 'redirect' => '...']

# Verificar columnas en tabla users
>>> DB::table('users')->first()
# Debe tener: google_id, google_email, auth_provider

# Revisar logs
tail -f storage/logs/laravel.log
```

---

## 🔐 Seguridad en Producción

### ✅ Headers de Seguridad

Asegúrate de que `config/app.php` tenga:

```php
'secure' => env('APP_SECURE', true), // HTTPS
'trusted_proxies' => env('TRUSTED_PROXIES', '*'),
'trusted_hosts' => env('TRUSTED_HOSTS', '.*'),
```

### ✅ CORS para Capacitor

Si la app móvil hace requests desde diferente dominio, verifica [cors.php](../config/cors.php):

```php
'allowed_origins' => [
    'https://offsideclub.com',
    'https://api.offsideclub.com',
    'capacitor://localhost',
],
```

### ✅ CSRF Protection

Asegúrate de que el callback de Google está excluido si es necesario:

```php
// En Middleware/VerifyCsrfToken.php
protected $except = [
    'auth/google/callback', // Agregar si tienes problemas
];
```

---

## 🛠️ Troubleshooting Producción

### Error: "Invalid OAuth state"
**Causa:** Almacenamiento de sesión revuelto
**Solución:** 
```php
// En GoogleAuthController::callback()
Socialite::driver('google')->stateless()->user();
```

### Error: "Redirect URI mismatch"
**Causa:** URL en Google Console no coincide
**Solución:** Verificar exactamente en Google Console:
```
Prod: https://api.offsideclub.com/auth/google/callback
```

### Error: "Session timeout during OAuth"
**Causa:** SESSION_LIFETIME muy baja
**Solución:** Aumentar en `.env`:
```env
SESSION_LIFETIME=120000 # 83 días
```

---

## 📊 Base de Datos - Validaciones

**Script SQL para verificar migración en prod:**

```sql
-- Verificar que campos existen
DESC users;
-- Debe mostrar: google_id, google_email, auth_provider

-- Verificar índices únicos
SHOW INDEXES FROM users WHERE Column_name = 'google_id';

-- Contar usuarios con Google OAuth
SELECT COUNT(*) as google_users FROM users WHERE google_id IS NOT NULL;
```

---

## 🎯 Dominios

**LOCAL:** http://offsideclub.test → http://localhost:8000

**PRODUCCIÓN:** https://app.offsideclub.es
