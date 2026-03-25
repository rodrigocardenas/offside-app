# 🎯 Fase 2: Integración con Controladores (Próximo)

**Prerequisitos:** Fase 1 Completada ✅  
**Duración Estimada:** 1 semana  
**Status Actual:** En Cola

---

## 📋 Tareas de Fase 2

### Tarea 1: Actualizar ProfileController (Avatares)

#### Archivos a Modificar:
- `app/Http/Controllers/ProfileController.php`

#### Cambios Requeridos:
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

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Procesar avatar si se subió
        if ($request->hasFile('avatar')) {
            try {
                $imageId = $this->cloudflareImages->upload(
                    $request->file('avatar'),
                    'avatars'
                );
                
                auth()->user()->update([
                    'avatar_cloudflare_id' => $imageId,
                    'avatar_provider' => 'cloudflare'
                ]);
                
            } catch (Exception $e) {
                return back()->withErrors(['avatar' => 'Error al subir imagen']);
            }
        }
        
        // ... resto del update
    }
}
```

---

### Tarea 2: Actualizar Migraciones BD

#### Crear Migración:
```bash
php artisan make:migration add_cloudflare_fields_to_users
```

#### Contenido:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_cloudflare_id')->nullable();
            $table->string('avatar_provider')->default('local'); // 'local' o 'cloudflare'
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_cloudflare_id', 'avatar_provider']);
        });
    }
};
```

---

### Tarea 3: Actualizar Modelo User

#### Cambios en `app/Models/User.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\CloudflareImages;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'avatar_cloudflare_id',  // NEW
        'avatar_provider',        // NEW
        // ...
    ];

    /**
     * Get avatar URL (Cloudflare o local storage)
     */
    public function getAvatarUrl($size = 'medium'): string
    {
        if ($this->avatar_provider === 'cloudflare' && $this->avatar_cloudflare_id) {
            return CloudflareImages::getTransformedUrl(
                $this->avatar_cloudflare_id,
                "avatar_{$size}"
            );
        }

        // Fallback a local
        return $this->avatar 
            ? asset("storage/avatars/{$this->avatar}")
            : asset('images/default-avatar.png');
    }

    /**
     * Get responsive srcset
     */
    public function getAvatarSrcset(): string
    {
        if ($this->avatar_provider === 'cloudflare' && $this->avatar_cloudflare_id) {
            return CloudflareImages::getResponsiveSet(
                $this->avatar_cloudflare_id,
                'avatar'
            );
        }

        return '';
    }
}
```

---

### Tarea 4: Actualizar Vistas Blade

#### Archivos a Modificar:
- `resources/views/profile/edit.blade.php`
- `resources/views/components/user-avatar.blade.php`
- `resources/views/profile/show.blade.php`
- Otras vistas que usen avatares

#### Cambios Ejemplo:
```blade
{{-- Antes --}}
<img src="{{ Storage::disk('public')->url('avatars/' . $user->avatar) }}"
     alt="{{ $user->name }}"
/>

{{-- Después --}}
<img src="{{ $user->getAvatarUrl('small') }}"
     srcset="{{ $user->getAvatarSrcset() }}"
     alt="{{ $user->name }}"
     class="avatar"
/>

{{-- O con Blade directive --}}
@cloudflareImageResponsive($user->avatar_cloudflare_id, $user->name, 'avatar')
```

---

### Tarea 5: Actualizar GroupController (Portadas)

#### Similar a ProfileController:
```php
public function updateCover(Request $request, Group $group)
{
    $request->validate(['cover' => 'required|image|max:102400']);

    try {
        $imageId = $this->cloudflareImages->upload(
            $request->file('cover'),
            'groups'
        );

        $group->update([
            'cover_cloudflare_id' => $imageId,
            'cover_provider' => 'cloudflare'
        ]);

        return back()->with('success', 'Portada actualizada');
    } catch (Exception $e) {
        return back()->withErrors(['cover' => 'Error al subir imagen']);
    }
}
```

---

### Tarea 6: Crear Tests de Integración

#### Crear Test File:
```bash
php artisan make:test ProfileControllerCloudflareTest
```

#### Contenido:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ProfileControllerCloudflareTest extends TestCase
{
    #[Test]
    public function can_upload_avatar_to_cloudflare()
    {
        Http::fake([
            'https://api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'image-id-123']
            ])
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put('/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg')
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->avatar_cloudflare_id);
        $this->assertEquals('cloudflare', $user->avatar_provider);
    }
}
```

---

### Tarea 7: Crear Comando de Migración (Opcional para Fase 2)

#### Crear Comando:
```bash
php artisan make:command MigrateLocalImagesToCloudflare
```

#### Propósito:
Migrar imágenes existentes de storage local a Cloudflare (se hará en Fase 3)

---

## 🗘 Dependencias Entre Tareas

```
Tarea 1 (ProfileController)
    ↓
Tarea 2 (Migraciones BD)
    ↓
Tarea 3 (Modelo User)
    ↓
Tarea 4 (Vistas Blade)
    ↓
Tarea 5 (GroupController)
    ↓
Tarea 6 (Tests)
    ↓
Tarea 7 (Comando migración)
```

---

## 📊 Checklist de Fase 2

### Checklist Técnico
- [ ] ProfileController actualizado
- [ ] GroupController actualizado
- [ ] Migraciones BD creadas
- [ ] Modelo User actualizado con helpers
- [ ] Vistas profile/* actualizadas
- [ ] Vistas groups/* actualizadas
- [ ] Tests de integración pasando
- [ ] Tests unitarios + integración: 100% green

### Checklist QA
- [ ] Avatar upload en profile funciona
- [ ] Avatar display en perfil con responsive
- [ ] Group cover upload funciona
- [ ] Group cover display con responsive
- [ ] Fallback a local cuando Cloudflare no disponible
- [ ] URLs cachean correctamente
- [ ] Logs registran operaciones

### Checklist Documentación
- [ ] CLOUDFLARE_IMAGES_PHASE2.md creado
- [ ] Ejemplos de controladores documentados
- [ ] Ejemplos de vistas documentados
- [ ] Cambios de BD documentados

---

## 🚀 Comandos para Ejecutar

### Durante Fase 2

```bash
# Crear migraciones
php artisan make:migration add_cloudflare_fields_to_users

# Crear tests de integración
php artisan make:test ProfileControllerCloudflareTest

# Ejecutar tests
php artisan test

# Ejecutar migraciones (después de validar)
php artisan migrate
```

---

## 🎯 Success Criteria

✅ Fase 2 estará completada cuando:

1. avatar/cover uploads usen Cloudflare
2. Las vistas muestren imágenes responsivas
3. Todos los tests pasen (unit + feature)
4. Fallback a storage local funcione
5. URLs estén cacheadas
6. Logging funcione
7. Documentación esté actualizada
8. Code review aprobado

---

## 📝 Notas Importantes

1. **Mantener Compatibilidad Hacia Atrás**
   - Usuarios con avatares locales deben seguir funcionando
   - Usar el campo `avatar_provider` para determinar origen

2. **Migración de Datos Existentes**
   - No migrar en Fase 2, solo estructura
   - La migración de datos existentes es Fase 3

3. **Testing**
   - Mock Cloudflare API con Http::fake()
   - Probar casos de éxito y fallback
   - Probar validaciones

4. **Performance**
   - Lazy load imágenes en listados
   - Usar srcset para responsive
   - Caché URLs en BD si es necesario

---

## 📞 Preguntas Frecuentes

**P: ¿Qué pasa si Cloudflare cae?**  
R: Fallback automático a storage local

**P: ¿Necesito espacios publicidad las imágenes antiguas?**  
R: No en Fase 2. Eso es Fase 3.

**P: ¿Puedo usar ambos proveedores simultáneamente?**  
R: Sí, el campo `avatar_provider` lo controla

**P: ¿Se necesitan cambios en frontend?**  
R: Cambios mínimos en Blade, principalmente en URLs

---

## 🆘 Soporte Durante Fase 2

Si encuentras problemas:
1. Revisar logs: `storage/logs/laravel.log`
2. Ejecutar health check: `CloudflareImages::isHealthy()`
3. Revisar tests de Fase 1
4. Consultar documentación de Fase 1

---

**Preparado:** 17 de Marzo de 2026  
**Estado Actual:** Fase 1 ✅ → Fase 2 🔜  
**Duración Estimada Fase 2:** 1 semana  
**Maintainer:** DevOps Team
