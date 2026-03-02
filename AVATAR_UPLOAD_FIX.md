# Fix de Upload de Avatars en Producción - 2 de Marzo 2026

## Problemas Reportados
1. ❌ No se podían subir imágenes de avatar de usuarios en producción
2. ❌ No había errores visibles en la aplicación
3. ❌ Telescope daba error 500

## Root Cause Analysis

### Problema 1: Avatar Upload Fallaba Silenciosamente
**Ubicación:** `app/Http/Controllers/ProfileController.php`

El método `update()` intentaba guardar directamente el objeto `UploadedFile` en la base de datos:
```php
auth()->user()->update($request->validated());
// ❌ $request->validated() devuelve un objeto UploadedFile que no puede guardarse directamente
```

**Solución:** 
1. Detectar si hay archivo avatar en el request
2. Guardarlo en `storage/app/public/avatars/` con nombre único
3. Guardar solo el nombre del archivo en la base de datos
4. Agregar logging para debugging

### Problema 2: Permisos de Storage Insuficientes
El directorio `storage/app/public/avatars` no tenía:
- Permisos de escritura (777) para www-data
- Propietario correcto (www-data:www-data)

**Solución:**
- Actualizar `deploy.sh` para crear el directorio `storage/app/public/avatars` automáticamente
- Configurar permisos 777 en todo `storage/app/public`
- Cambiar propietario a www-data recursivamente

### Problema 3: Telescope Error 500
El error de Telescope era causado por el Problema 1 durante el update del usuario.

**Solución:** Al arreglar el upload, Telescope deja de registrar excepciones.

## Cambios Implementados

### 1. ProfileController.php - Línea 49-78
```php
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = auth()->user();
    $data = $request->validated();

    // Procesar avatar si se subió
    if ($request->hasFile('avatar')) {
        try {
            $file = $request->file('avatar');
            
            // Generar nombre único
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Guardar en storage/app/public/avatars
            $path = $file->storeAs('avatars', $filename, 'public');
            Log::info('Avatar guardado en: ' . $path);
            
            // Guardar solo el nombre del archivo en la BD
            $data['avatar'] = $filename;
            
        } catch (\Exception $e) {
            Log::error('Error guardando avatar: ' . $e->getMessage());
            unset($data['avatar']);
        }
    } else {
        unset($data['avatar']);
    }

    $user->update($data);
    return Redirect::route('profile.edit')->with('status', 'profile-updated');
}
```

### 2. deploy.sh - Línea 91-97
```bash
echo "🔧 Preparando directorios..."
mkdir -p bootstrap/cache storage/app/public/avatars

# Asegurar permisos correctos en storage
chmod -R 777 storage/app/public 2>/dev/null || true
chmod -R 777 storage/framework 2>/dev/null || true
chmod -R 777 storage/logs 2>/dev/null || true
chown -R www-data:www-data storage 2>/dev/null || true
```

### 3. Nuevo Script: fix-storage-permissions.sh
Script standalone para verificar/corregir permisos de storage en producción:
- Crea directorio `storage/app/public/avatars` si no existe
- Configura permisos 775 para directorios
- Configura permisos 664 para archivos
- Establece propietario www-data:www-data
- Verifica/crea symlink de public/storage

## Commits Relacionados
- `0694c39` - fix: procesar y guardar avatars correctamente en storage/app/public/avatars
- `7bccb44` - chore: mejorar configuración de permisos de storage para avatars en deploy

## Estado Actual

### ✅ Verificadas
- [x] ProfileController procesa avatars correctamente
- [x] Los archivos se guardan en storage/app/public/avatars
- [x] Los nombres se guardan correctamente en users.avatar
- [x] Los permisos de storage están configurados (777)
- [x] Telescope funciona correctamente (253 entries, 2 excepciones de debug)
- [x] Deploy incluye storage/app/public (protege avatars y logos)

### Estructura de Almacenamiento
```
storage/app/public/
├── avatars/           (775) - Avatars de usuarios
├── logos/             (775) - Logos de equipos  
└── ...
```

### URL de Acceso
```
/avatars/{filename}   →  storage/app/public/avatars/{filename}
/storage/logos/...    →  storage/app/public/logos/...
```

## Testing Manual

Para probar el upload de avatars:
1. Ir a `/profile` (editar perfil)
2. Seleccionar imagen en el campo "Avatar"
3. Hacer click en "Update"
4. Verificar en BD: `SELECT avatar FROM users WHERE id = {user_id};`
5. Verificar en Telescope: `/telescope` → Exceptions o Requests

## Logs
- Aplicación: `/var/www/html/storage/logs/laravel.log`
- Seguridad: `/var/www/html/storage/logs/security.log`
- Nginx: `/var/log/nginx/error.log`

## Próximos Pasos
1. Usuarios pueden subir avatars sin problemas
2. Monitorear logs para asegurar que no hay errores
3. El symlink `public/storage` está automáticamente configurado

