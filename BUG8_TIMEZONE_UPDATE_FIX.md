# 🔧 Fix Bug #8: Actualización de Zona Horaria en Login

**Fecha:** 28 enero 2026  
**Status:** ✅ Completado  
**Problema:** Al ingresar a la app, la columna `timezone` no se actualizaba automáticamente  

---

## 📋 Problema Identificado

Aunque la implementación del Bug #8 (zona horaria en preguntas) estaba completa, había una deficiencia crítica:

- **Usuarios nuevos:** Se guardaba el timezone al crear la cuenta
- **Usuarios existentes:** El timezone NO se actualizaba cuando volvían a iniciar sesión
- **Resultado:** Los usuarios que cambiaban de dispositivo/zona horaria no veían los cambios reflejados

El usuario necesitaba que:
```
✅ Se actualice el timezone en CADA login
✅ Se actualice aunque el usuario ya tenga un valor guardado
✅ Se sincronice automáticamente desde el dispositivo sin intervención del usuario
```

---

## ✅ Solución Implementada

### 1️⃣ Backend - Actualización del Endpoint API

**Archivo:** [routes/api.php](routes/api.php)

**Agregado:** Nuevo endpoint `/api/set-timezone` (POST)

```php
Route::post('/set-timezone', function (Request $request) {
    $request->validate([
        'timezone' => 'required|string|timezone',
    ]);

    $request->user()->update([
        'timezone' => $request->timezone,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Zona horaria actualizada correctamente',
        'timezone' => $request->timezone,
    ]);
});
```

**Características:**
- ✅ Valida que timezone sea válido (validador `timezone` de Laravel)
- ✅ **SIEMPRE actualiza** aunque ya exista un valor
- ✅ Retorna confirmación en JSON
- ✅ Protegido con middleware `auth:sanctum`

---

### 2️⃣ Backend - Actualización del LoginController

**Archivo:** [app/Http/Controllers/Auth/LoginController.php](app/Http/Controllers/Auth/LoginController.php)

**Cambios:**
1. Agregar validación para recibir `timezone` en la request
2. Al crear usuario: guardar timezone
3. Al usuario existente: **SIEMPRE actualizar** si viene en la request

```php
public function login(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'timezone' => 'nullable|string|timezone',  // ✅ NUEVO
    ]);

    // ... código existente ...

    if (!$user) {
        // Determinar timezone
        $timezone = $request->timezone ?? config('app.timezone');

        $user = User::create([
            'name' => $baseName,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'timezone' => $timezone,  // ✅ Guardar en nuevo usuario
        ]);
    } else {
        // ✅ IMPORTANTE: SIEMPRE actualizar si viene timezone
        if ($request->filled('timezone')) {
            $user->update(['timezone' => $request->timezone]);
        }
    }

    Auth::login($user);
}
```

---

### 3️⃣ Frontend - Formulario de Login

**Archivo:** [resources/views/auth/login.blade.php](resources/views/auth/login.blade.php)

**Cambios:**
1. Agregar campo oculto `<input type="hidden" name="timezone">`
2. Script JavaScript que captura el timezone antes de enviar el formulario

```blade
<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- ✅ Campo oculto para timezone -->
    <input type="hidden" id="timezone" name="timezone" value="">

    <!-- Campo username -->
    <input id="name" type="text" name="name" required>
    
    <!-- Botón envío -->
    <button type="submit">Iniciar Sesión</button>
</form>

<script>
    // ✅ Capturar timezone automáticamente
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            document.getElementById('timezone').value = timezone;
        } catch (e) {
            console.warn('No se pudo detectar timezone');
        }
    });
</script>
```

---

### 4️⃣ Frontend - Script de Sincronización Continua

**Archivo:** [public/js/timezone-sync.js](public/js/timezone-sync.js) ✨ NUEVO

**Propósito:** Sincronizar timezone automáticamente en CADA acceso a la app

**Características:**
- ✅ Se ejecuta al cargar cualquier página (para usuarios autenticados)
- ✅ Detecta timezone del dispositivo automáticamente
- ✅ Envía al endpoint `/api/set-timezone` si es diferente o venció el cache
- ✅ Implementa cache local (6 horas) para evitar requests innecesarias
- ✅ Se re-sincroniza cuando el usuario regresa a la app después de inactividad (30 min)

```javascript
// Detectar timezone del dispositivo
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

// Sincronizar con servidor
fetch('/api/set-timezone', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({ timezone })
});
```

---

### 5️⃣ Frontend - Integración en Layout

**Archivo:** [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php)

**Cambios:**
1. Agregar meta tag `user-id` (para detectar si está autenticado)
2. Incluir script `timezone-sync.js`

```blade
<!-- Meta para detectar usuario autenticado -->
@auth
    <meta name="user-id" content="{{ auth()->user()->id }}">
@endauth

<!-- Scripts -->
<script src="{{ asset('js/timezone-sync.js') }}"></script>
```

---

## 🔄 Flujo Completo

### Usuario Nuevo
```
1. Accede a /login
2. JavaScript captura timezone del dispositivo
3. Envía formulario con timezone
4. LoginController crea usuario + guarda timezone
5. ✅ Usuario creado con timezone correcto
```

### Usuario Existente (primer acceso del día)
```
1. Accede a /
2. Script timezone-sync.js se ejecuta
3. Detecta timezone del dispositivo
4. Verifica si es diferente o hace más de 6 horas
5. Envía POST /api/set-timezone
6. ✅ Timezone actualizado en BD
7. Preguntas se muestran en zona horaria correcta
```

### Usuario Existente (regresa después de inactividad)
```
1. Sale de app (minimiza/cierra)
2. Vuelve a abrir app (window focus event)
3. Verifica inactividad > 30 minutos
4. Re-sincroniza timezone
5. ✅ Timezone actualizado nuevamente
```

---

## 📊 Casos de Uso Cubiertos

| Caso | Antes | Ahora |
|------|-------|-------|
| Usuario nuevo en login | ✅ Se guardaba | ✅ Se guarda |
| Usuario existente en login | ❌ NO se actualizaba | ✅ Se actualiza |
| Usuario cambia de dispositivo | ❌ Mantiene viejo timezone | ✅ Se actualiza en login |
| Usuario viaja a otra zona | ❌ NO se detecta | ✅ Se sincroniza automáticamente |
| Horas de partidos para usuario | ❌ Zona horaria incorrecta | ✅ Zona horaria correcta |

---

## 🧪 Testing

### Test Manualmente (Usuario Nuevo)
```bash
# 1. Ir a /login
# 2. Inspeccionar elemento → Network
# 3. Enviar formulario
# 4. Verificar que timezone viene en POST body:
#    timezone: "America/Bogota"
# 5. Verificar en BD:
#    SELECT name, timezone FROM users WHERE email LIKE '%@offsideclub%';
```

### Test Manualmente (Usuario Existente)
```bash
# 1. Autenticarse normalmente
# 2. Abrir DevTools → Application → Local Storage
# 3. Actualizar página (F5)
# 4. Ver Network → POST /api/set-timezone
# 5. Verificar que timezone se actualizó
# 6. Verficar en BD que cambió el valor
```

### Test en Tinker
```bash
$ php artisan tinker

>>> $user = User::first()
>>> $user->timezone
# "Europe/Madrid"

>>> # Simular que viene request con otro timezone
>>> $user->update(['timezone' => 'America/Bogota'])

>>> # Verificar en preguntas
>>> $q = Question::with('footballMatch')->first()
>>> $q->available_until->timezone('America/Bogota')->format('Y-m-d H:i')
```

---

## 🔐 Seguridad

✅ **Validación de timezone:**
- Laravel validador `timezone` verifica que sea válido
- Rechaza valores inválidos

✅ **Autenticación:**
- Endpoint `/api/set-timezone` requiere `auth:sanctum`
- Solo usuarios autenticados pueden actualizar su timezone

✅ **CSRF Protection:**
- Formulario de login incluye `@csrf`
- Script de sincronización usa token de CSRF

---

## 📁 Archivos Modificados

| Archivo | Cambios | Tipo |
|---------|---------|------|
| [routes/api.php](routes/api.php) | +POST /api/set-timezone | Endpoint |
| [app/Http/Controllers/Auth/LoginController.php](app/Http/Controllers/Auth/LoginController.php) | Validar + actualizar timezone | Backend |
| [resources/views/auth/login.blade.php](resources/views/auth/login.blade.php) | Campo hidden + script | Frontend |
| [public/js/timezone-sync.js](public/js/timezone-sync.js) | ✨ NUEVO | Script |
| [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php) | Meta tag + include script | Frontend |

---

## 📝 Notas Técnicas

### Intl.DateTimeFormat API
```javascript
// Obtiene timezone del navegador/dispositivo (muy preciso)
const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
// Retorna strings como: "America/Bogota", "Europe/Madrid", "Australia/Sydney"
```

### Cache Local
```javascript
// Se implementó cache de 6 horas para evitar:
// - Requests innecesarias al servidor
// - Latencia en carga de página
// Se re-sincroniza si:
// - Timezone cambió
// - Hace más de 6 horas que se sincronizó
// - Usuario regresa después de 30 min inactivo
```

### Diferencia con Zona Horaria Manual
```
❌ Opción 1 (Manual): Usuario debe seleccionar timezone en perfil
   - Requiere acción del usuario
   - Fácil de olvidar actualizar
   - No se sincroniza cuando viaja

✅ Opción 2 (Automática): Detectar del dispositivo
   - Sin intervención del usuario
   - Se sincroniza en cada login
   - Se re-sincroniza si cambia dispositivo/zona
```

---

## ✨ Beneficios

1. **Experiencia del usuario mejorada:**
   - No necesita configurar timezone manualmente
   - Funciona automáticamente en cualquier dispositivo

2. **Horas precisas:**
   - Las horas de partidos se muestran correctamente según zona horaria del usuario
   - No hay confusión sobre horarios

3. **Cobertura global:**
   - Usuarios en cualquier zona horaria ven horas correctas
   - Funciona perfectamente para usuarios internacionales

4. **Bajo overhead:**
   - Cache de 6 horas evita requests innecesarias
   - Sincronización solo cuando es necesario

---

## 🚀 Próximos Pasos (Opcional)

1. Agregar opción manual en perfil del usuario para override
2. Mostrar notificación visual cuando se detecta cambio de timezone
3. Agregar analytics para trackear cambios de zona
4. Implementar respuesta a cambios de timezone en tiempo real (WebSocket)

---

**Implementación completada: ✅ 28 enero 2026**
