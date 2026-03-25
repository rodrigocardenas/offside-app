# Fase 1: Configuración Base de Cloudflare Images ✅

**Completado:** 17 de Marzo de 2026  
**Status:** ✅ Completado

---

## 📋 Tareas Realizadas

### 1.1 Crear Cuenta en Cloudflare Images
- [x] Obtener `CLOUDFLARE_ACCOUNT_ID`
- [x] Obtener `CLOUDFLARE_API_TOKEN`
- [x] Obtener `CLOUDFLARE_IMAGES_DOMAIN`
- [x] Configurar variables de entorno

### 1.2 Crear Configuración en Laravel

#### Archivos Creados:
- ✅ [config/cloudflare.php](../config/cloudflare.php) - Configuración principal
  - Valores por defecto sensatos
  - Transformaciones predefinidas
  - Anchos responsivos configurables
  - Opciones de caché y logging

#### Variables de Entorno Agregadas:
- ✅ [.env.example](.env.example#L73) - Configuración para desarrollo
- ✅ [.env.production.example](.env.production.example#L28) - Configuración para producción

**Variables Principales:**
```env
CLOUDFLARE_IMAGES_ENABLED=true
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_IMAGES_DOMAIN=
CLOUDFLARE_IMAGES_FALLBACK_DISK=public
CLOUDFLARE_IMAGES_ENABLE_FALLBACK=true
CLOUDFLARE_UPLOAD_TIMEOUT=30
CLOUDFLARE_UPLOAD_RETRIES=3
```

### 1.3 Crear Servicio CloudflareImages

#### Archivo Creado:
- ✅ [app/Services/CloudflareImagesService.php](../app/Services/CloudflareImagesService.php)

#### Métodos Implementados:
```php
// Upload
public function upload(UploadedFile $file, string $directory = ''): string
public function batch(array $files, string $directory = ''): array

// Acceso
public function getUrl(string $imageId, array $options = []): string
public function getTransformedUrl(string $imageId, string $transformKey): string
public function getResponsiveSet(string $imageId, string $imageType = 'avatar'): string

// Gestión
public function delete(string $imageId): bool
public function isHealthy(): bool
```

### 1.4 Crear Service Provider

#### Archivos Creados:
- ✅ [app/Providers/CloudflareServiceProvider.php](../app/Providers/CloudflareServiceProvider.php)
  - Registra el servicio como singleton
  - Registra Blade directives
  - Inyección de dependencias

#### Blade Directives Agregados:
```blade
@cloudflareImage($id, $alt, $transformKey, $attributes)
@cloudflareImageResponsive($id, $alt, $imageType, $attributes)
@cloudflarePicture($id, $alt, $imageType, $attributes)
@cloudflareUrl($id, $options)
@cloudflareTransform($id, $transformKey)
@cloudflareBackground($id, $options)
```

### 1.5 Crear Facade

#### Archivo Creado:
- ✅ [app/Facades/CloudflareImages.php](../app/Facades/CloudflareImages.php)

**Uso en Controladores:**
```php
use App\Facades\CloudflareImages;

// Upload
$imageId = CloudflareImages::upload($file, 'avatars');

// Obtener URL
$url = CloudflareImages::getUrl($imageId, ['width' => 400]);

// Verificar salud
if (CloudflareImages::isHealthy()) {
    // Usar Cloudflare
}
```

### 1.6 Crear Helpers

#### Archivo Creado:
- ✅ [app/Helpers/CloudflareImagesHelper.php](../app/Helpers/CloudflareImagesHelper.php)

**Métodos Disponibles:**
```php
CloudflareImagesHelper::url($id, $options)
CloudflareImagesHelper::transform($id, $key)
CloudflareImagesHelper::responsive($id, $type)
CloudflareImagesHelper::img($id, $alt, $key, $attrs)
CloudflareImagesHelper::imgResponsive($id, $alt, $type, $attrs)
CloudflareImagesHelper::picture($id, $alt, $type, $attrs)
CloudflareImagesHelper::backgroundImage($id, $options)
CloudflareImagesHelper::isAvailable()
```

### 1.7 Crear Tests Unitarios

#### Archivo Creado:
- ✅ [tests/Unit/Services/CloudflareImagesServiceTest.php](../tests/Unit/Services/CloudflareImagesServiceTest.php)

**Resultados:**
```
✓ 21 tests passed (34 assertions)
✓ Duration: 2.88s
```

**Tests Implementados:**
1. Upload exitoso
2. Upload con fallback a storage local
3. Upload cuando está deshabilitado
4. Delete exitoso
5. Delete fallido
6. Get URL con transformaciones
7. Get URL sin transformaciones
8. Get URL con fallback a local
9. Get transformed URL con preset
10. Get responsive set
11. Batch upload con éxito y fracaso mixtos
12. Health check exitoso
13. Health check fallido
14. Health check cuando está deshabilitado
15. Health check sin credenciales
16. Validación de archivo JPEG válido
17. Validación de archivo inválido
18. Upload con reintento en fallida
19. Get URL con caché
20. Upload retorna path cuando está deshabilitado
21. Múltiples transformaciones en URL

---

## 📚 Guía de Uso Rápido

### En Controladores (Inyección de Dependencias)

```php
<?php

namespace App\Http\Controllers;

use App\Services\CloudflareImagesService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private CloudflareImagesService $cloudflareImages
    ) {}

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:102400'
        ]);

        try {
            $imageId = $this->cloudflareImages->upload(
                $request->file('avatar'),
                'avatars'
            );

            auth()->user()->update(['avatar_cloudflare_id' => $imageId]);

            return back()->with('success', 'Avatar actualizado');
        } catch (Exception $e) {
            return back()->withErrors(['avatar' => $e->getMessage()]);
        }
    }
}
```

### Con Facade

```php
<?php

use App\Facades\CloudflareImages;

// Upload
$imageId = CloudflareImages::upload($file, 'avatars');

// Get URL
$url = CloudflareImages::getUrl($imageId, [
    'width' => 400,
    'height' => 400,
    'crop' => 'cover',
    'quality' => 'auto'
]);

// Get transformed URL
$url = CloudflareImages::getTransformedUrl($imageId, 'avatar_small');

// Get responsive image set
$srcset = CloudflareImages::getResponsiveSet($imageId, 'avatar');

// Check if available
if (CloudflareImages::isHealthy()) {
    // Use Cloudflare
}
```

### En Blade Templates

```blade
{{-- Simple image --}}
@cloudflareImage('image-id', 'Alt text', 'avatar_small')

{{-- Responsive image --}}
@cloudflareImageResponsive('image-id', 'Alt text', 'avatar')

{{-- Picture with WebP fallback --}}
@cloudflarePicture('image-id', 'Alt text', 'group_cover')

{{-- Get URL for src attribute --}}
<img src="@cloudflareUrl('image-id', ['width' => 400])" alt="Photo" />

{{-- Transform preset --}}
<img src="@cloudflareTransform('image-id', 'avatar_small')" alt="Avatar" />

{{-- Background image --}}
<div style="@cloudflareBackground('image-id')">
    Content
</div>

{{-- Using helper directly --}}
<img src="{{ CloudflareImagesHelper::url('image-id') }}" alt="Photo" />
```

---

## 🔧 Configuración

### En `.env` (Desarrollo)

```env
CLOUDFLARE_IMAGES_ENABLED=false  # Deshabilitado en desarrollo
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_IMAGES_DOMAIN=
```

### En `.env.production` (Producción)

```env
CLOUDFLARE_IMAGES_ENABLED=true
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_token
CLOUDFLARE_IMAGES_DOMAIN=https://your-account.images.cloudflare.com
CLOUDFLARE_IMAGES_FALLBACK_DISK=public
CLOUDFLARE_IMAGES_ENABLE_FALLBACK=true
CLOUDFLARE_UPLOAD_TIMEOUT=30
CLOUDFLARE_UPLOAD_RETRIES=3
CLOUDFLARE_UPLOAD_RETRY_DELAY=1000
CLOUDFLARE_CACHE_ENABLED=true
CLOUDFLARE_CACHE_TTL=86400
CLOUDFLARE_LOGGING_ENABLED=true
CLOUDFLARE_LOG_CHANNEL=stack
```

---

## 📦 Transformaciones Predefinidas

### Avatares
```php
'avatar_small' => [
    'width' => 120,
    'height' => 120,
    'crop' => 'cover',
    'quality' => 'auto',
],

'avatar_medium' => [
    'width' => 400,
    'height' => 400,
    'crop' => 'cover',
    'quality' => 'auto',
],
```

### Logos
```php
'logo' => [
    'width' => 200,
    'quality' => 'auto',
    'format' => 'auto',
],
```

### Portadas de Grupos
```php
'group_cover' => [
    'width' => 1920,
    'height' => 1080,
    'crop' => 'cover',
    'quality' => 'auto',
    'format' => 'auto',
],

'group_cover_mobile' => [
    'width' => 768,
    'height' => 512,
    'crop' => 'cover',
    'quality' => 'auto',
    'format' => 'auto',
],
```

---

## 🎯 Próximos Pasos

### Fase 2: Integración con Controladores
- [ ] Actualizar ProfileController para usar CloudflareImages
- [ ] Actualizar GroupController para imágenes de grupo
- [ ] Crear Blade directives mejorados
- [ ] Actualizar vistas

### Fase 3: Migración de Imágenes
- [ ] Crear comando artisan `cloudflare:migrate-images`
- [ ] Migrar imágenes existentes desde storage local
- [ ] Actualizar referencias en base de datos
- [ ] Verificar integridad

---

## 🔍 Troubleshooting

### El servicio retorna 401 Unauthorized
**Solución:** Verificar `CLOUDFLARE_API_TOKEN` en `.env`

### Las imágenes no se cargan desde Cloudflare
**Solución:** Asegurar que `CLOUDFLARE_IMAGES_DOMAIN` comienza con `https://`

### El fallback a storage local se activa constantemente
**Solución:** Revisar logs en `storage/logs/laravel.log` para errores de API

### URLs vacías cuando está deshabilitado
**Solución:** Asegurar que `CLOUDFLARE_IMAGES_ENABLE_FALLBACK=true`

---

## 📊 Testing

Ejecutar todos los tests:
```bash
php artisan test tests/Unit/Services/CloudflareImagesServiceTest.php
```

Resultado esperado:
```
✓ 21 tests passed (34 assertions)
```

---

## 📝 Notas Adicionales

1. **Seguridad:** El API token NO debe commitearse. Usar `.env` local y variables de entorno en producción.

2. **Fallback:** El servicio cae automáticamente a storage local si Cloudflare no está disponible.

3. **Caché:** Las URLs se cachean durante 24 horas (configurable en `.env`).

4. **Logging:** Todos los uploads y errores se loguean automáticamente.

5. **Rate Limiting:** Implementar en el controller si es necesario.

---

**Status:** ✅ Fase 1 Completada  
**Próxima:** Fase 2 - Integración con Controladores  
**Última Actualización:** 17 de Marzo de 2026
