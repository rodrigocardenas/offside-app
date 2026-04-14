# 🔐 Plan: Implementación de Login con Google  

**Objetivo:** Mantener login actual (basado en nombre/nickname) y agregar alternativa con Google OAuth en ruta separada

---

## 📋 Estado Actual

| Aspecto | Valor |
|--------|-------|
| **Auth System** | Session-based (Laravel) + Sanctum para API |
| **Login Actual** | Basado en nombre (sin contraseña) |
| **Base de Datos** | MySQL local (Laragon) / RDS en producción |
| **User Model** | Tiene campos: `name`, `email`, `password`, `avatar` |
| **URL Actual** | `/auth/login` (POST) → crea o busca usuario por nombre |

---

## 🚀 Opción 1: Google OAuth + RDS (RECOMENDADO)

### Arquitectura
```
Google OAuth 2.0 (Flow Implicit/Authorization Code)
        ↓
  App Frontend (Capacitor)
        ↓
Laravel Backend (OAuth Router Socialite)
        ↓
RDS MySQL (Datos de usuario)
```

### Pros ✅
- **Seguridad:** Contraseñas gestionadas por Google, no tocas credenciales
- **UX:** Login de un click, sin crear contraseña
- **Escalable:** RDS en producción maneja bien OAuth sessions
- **Datos Locales:** Control total sobre datos de usuarios en tu RDS
- **Capacitor Compatible:** Funciona bien con WebView
- **Control:** Puedes customizar el User model como quieras

### Contras ❌
- Más setup inicial (crear app en Google Cloud Console)
- Debes mantener servidor OAuth backend alive
- Session management depende de tu infraestructura RDS

### Requisitos
1. **Google Cloud Console:**
   - Crear proyecto
   - OAuth 2.0 Client ID (Web Application)
   - Configurar redirect URIs: `http://offsideclub.test/auth/google/callback`, `https://api.prod.com/auth/google/callback`

2. **Paquetes Laravel:**
   ```bash
   composer require laravel/socialite
   composer require guzzlehttp/guzzle
   ```

3. **Tabla Users Modificada:**
   ```sql
   ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE;
   ALTER TABLE users ADD COLUMN google_email VARCHAR(255);
   ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255);
   ```

### Flujo Implementación

```
RUTA ACTUAL (mantener igual):
GET  /login           → showLoginForm()
POST /login           → login(name, timezone) → crea/busca user por nombre

RUTA NUEVA (Google):
GET  /login/google    → redirectToGoogle()    → OAuth consent
GET  /auth/google/callback → handleCallback() → verifica token
     → busca/crea user con google_id
     → Auth::login($user)
     → redirige a dashboard
```

---

## 🔥 Opción 2: Firebase Authentication + Firestore

### Arquitectura
```
Google Firebase (Managed Service)
  ├── Authentication (OAuth + User Management)
  ├── Firestore (NoSQL)
  └── Cloud Functions (Optional)
        ↓
Laravel Backend (validar token de Firebase)
        ↓
Sincronizar datos a RDS si necesitas
```

### Pros ✅
- **Zero Setup de OAuth:** Google maneja todo (no necesitas Cloud Console)
- **Real-time DB:** Firestore es excelente para chat/notificaciones
- **Serverless:** No mantienes servers OAuth
- **Firebase Admin SDK:** Fácil validar tokens sin implementar OAuth
- **Push Notifications:** Desacoplado de tu servidor

### Contras ❌
- **Vendor Lock-in:** Atado a Google Cloud
- **Costos:** Firestore puede ser caro con alta concurrencia
- **Migración Futura:** Si dejas Firebase es complicado
- **Tu RDS se Queda Atrás:** Necesitas sincronización compleja
- **No es DB relacional:** Perderías relaciones User-Group, User-Predictions
- **Control Reducido:** Datos en Firebase, no en tu infraestructura

### Consideración Crítica ⚠️
Con tu arquitectura (predictions, groups, tournaments), **necesitas relaciones SQL**. Firebase es mejor para aplicaciones desacopladas, no para plataforma de predicciones.

---

## 🌐 Opción 3: Supabase (PostgreSQL + Auth)

### Arquitectura
```
Supabase (Managed PostgreSQL + Auth)
  ├── PostgreSQL DB (con OAuth built-in)
  ├── Auth (Google OAuth sin backend)
  └── Real-time Subscriptions
        ↓
Laravel Backend (conecta a Supabase PostgreSQL)
        ↓
Sin necesidad de RDS
```

### Pros ✅
- **Auth + DB Integrados:** OAuth dentro de Supabase, sin configurar Google Console
- **PostgreSQL:** Base de datos relacional completa
- **Migraciones de Datos:** Fácil desde MySQL con migration tools
- **Real-time Broadcasting:** Built-in (útil para chat/notificaciones)
- **Open Source:** Si necesitas migrar, código está disponible

### Contras ❌
- **Vendor Lock-in 2.0:** Pero menos severo que Firebase
- **No es RDS:** Pierdes familiaridad con infraestructura AWS
- **Curva Aprendizaje:** Diferente a MySQL tradicional (aunque es SQL)
- **Costo:** Similar a Firebase en alta concurrencia
- **Menos Support:** Comunidad menor que Firebase o RDS

---

## 📊 Comparación Resumida

| Criterio | Google + RDS | Firebase | Supabase |
|----------|-------------|----------|----------|
| **Seguridad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Control** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Costo** | ⭐⭐⭐⭐ (RDS bajo) | ⭐⭐ (caro) | ⭐⭐⭐ |
| **Setup** | ⭐⭐⭐ (medio) | ⭐⭐⭐⭐⭐ (fácil) | ⭐⭐⭐⭐ |
| **Relaciones SQL** | ⭐⭐⭐⭐⭐ | ❌ | ⭐⭐⭐⭐⭐ |
| **Capacitor** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Escalabilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

---

## 🎯 **RECOMENDACIÓN: Opción 1 (Google OAuth + RDS)**

### Por qué:
1. **Tu arquitectura lo permite:** Ya tienes RDS en producción
2. **Control máximo:** Datos en tu infraestructura
3. **Mejor para BD relacional:** Predictions, Groups, Rankings necesitan JOINs
4. **Migración futura fácil:** Si cambias OAuth provider, los datos quedan
5. **No vendor lock-in:** Google OAuth es estándar

### Riesgos Mitigados:
- ✅ Guardar backup de User model antes de agregar campos Google
- ✅ Crear tabla pivote `user_oauth_providers` para permitir múltiples logins
- ✅ Mantener login actual funcionando (zero breaking changes)

---

## 🛠️ Plan de Implementación (Opción 1)

### Fase 1: Setup Infraestructura (30 min)
```bash
# 1. Crear app en Google Cloud Console
# 2. Obtener GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET
# 3. Agregar al .env:
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_CALLBACK_URL=http://offsideclub.test/auth/google/callback

# 4. Instalar Socialite
composer require laravel/socialite
```

### Fase 2: Modificar BD (10 min)
```sql
-- Tabla nueva para múltiples OAuth providers
CREATE TABLE user_oauth_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider VARCHAR(50),           -- 'google', 'facebook', etc
    provider_id VARCHAR(255),       -- google_id de Google
    provider_email VARCHAR(255),
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(provider, provider_id)
);

-- O más simple: agregar a tabla users
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE;
ALTER TABLE users ADD COLUMN google_email VARCHAR(255);
```

### Fase 3: Crear Routes + Controller (1 hora)
```php
// routes/web.php
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback')->name('auth.google.callback');

// App/Http/Controllers/Auth/GoogleAuthController.php
// - redirect(): return Socialite::driver('google')->redirect();
// - callback(): procesar token Google, crear/actualizar User
```

### Fase 4: Crear Vista Login con Botón Google (30 min)
```html
<!-- resources/views/auth/login.blade.php -->
<!-- Botón actual para login por nombre -->
<!-- + Nuevo botón: "Login con Google" -->
```

### Fase 5: Testing (1 hora)
- Test flow completo: Click Google → Consent → Callback → Error handling
- Sincronización con Capacitor
- Verificar que login actual NO se rompe

---

## 🎮 Recursos Útiles

### Google OAuth en Laravel
- [Laravel Socialite](https://laravel.com/docs/11/socialite)
- [Google OAuth Setup](https://socialite.laravel.com/version/master/providers/google)

### Para Capacitor
- [OAuth con WebView](https://capacitorjs.com/docs/guides/auth-flows)
- Usar `InAppBrowser` para OAuth flow

### Seguridad
- [CSRF Protection](https://laravel.com/docs/11/csrf)
- [OAuth 2.0 Best Practices](https://tools.ietf.org/html/draft-ietf-oauth-security-topics)

---

## ❓ Next Steps

1. **Confirmar:** ¿Quieres ir con Google OAuth + RDS?
2. **Si sí:** ¿Empezamos con Fase 1 (Google Cloud Console)?
3. **Si no:** ¿Quieres comparar Firebase vs Supabase más detalladamente?

---

## 📌 Checklist Pre-Implementación

- [ ] Google Cloud Console accesible
- [ ] RDS credentials actualizadas en .env
- [ ] Backup de DB actual
- [ ] Teste del LoginController actual (antes de cambios)
- [ ] Rama feature creada: `git checkout -b feat/google-oauth`
