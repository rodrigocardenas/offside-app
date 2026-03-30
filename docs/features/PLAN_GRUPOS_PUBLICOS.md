# Plan de Implementación: Grupos Públicos con Expiración

## 📋 Resumen Ejecutivo
Reemplazar el componente de "Partido Destacado" en la vista principal de grupos con un nuevo componente que muestre un "Grupo Público" (categoría nueva). Los grupos públicos serán creados solo por admins, aparecerán en el inicio de la app, y se auto-eliminarán cuando lleguen a su fecha de expiración.

---

## 🎯 Objetivos

1. **Crear categoría "public"** para grupos con nueva lógica de ciclo de vida
2. **Agregar fecha de expiración** a la tabla `groups` para control automático
3. **Restricción de permisos** - Solo admins pueden crear grupos públicos
4. **Componente nuevo** - Reemplazar featured-match con featured-group
5. **Auto-eliminación** - Limpieza automática de grupos expirados via scheduler
6. **UI/UX** - Similar a featured-match pero para mostrar detalles del grupo

---

## 🗂️ Estructura de Cambios

### Fase 1: Base de Datos
**Archivo:** `database/migrations/YYYY_MM_DD_XXXXXX_add_expiration_to_groups_table.php`

```php
// Nueva columna
$table->dateTime('expires_at')->nullable()->index();
```

**Cambios:**
- ✅ Crear migración para agregar `expires_at` a tabla `groups`
- ✅ El campo será nullable para compatibilidad con grupos existentes

---

### Fase 2: Modelo Group
**Archivo:** `app/Models/Group.php`

**Cambios:**
- ✅ Agregar `expires_at` a `$fillable`
- ✅ Agregar mutador `$casts` para convertir a Carbon
- ✅ Método `isExpired()` - Retorna si el grupo expiró
- ✅ Método `isPublic()` - Retorna si es categoría "public"
- ✅ Scope `active()` - Filtra grupos no expirados
- ✅ Scope `public()` - Filtra solo grupos públicos

```php
protected $fillable = [
    'name',
    'code',
    'created_by',
    'competition_id',
    'category',
    'reward_or_penalty',
    'expires_at'
];

protected $casts = [
    'expires_at' => 'datetime'
];

public function isExpired(): bool {
    return $this->expires_at && $this->expires_at->isPast();
}

public function isPublic(): bool {
    return $this->category === 'public';
}

public function scopeActive($query) {
    return $query->where(function($q) {
        $q->whereNull('expires_at')
          ->orWhere('expires_at', '>', now());
    });
}

public function scopePublic($query) {
    return $query->where('category', 'public');
}
```

---

### Fase 3: GroupController
**Archivo:** `app/Http/Controllers/GroupController.php`

**Cambios en `index()`:**
- ✅ Reemplazar `getFeaturedMatch()` por `getFeaturedPublicGroup()`
- ✅ Pasar `featuredGroup` a la vista en lugar de `featuredMatch`
- ✅ Mantener separación de grupos oficiales y amateur

```php
// Antes:
$featuredMatch = $this->getFeaturedMatch($groups);

// Después:
$featuredGroup = $this->getFeaturedPublicGroup();
```

**Nuevo método `getFeaturedPublicGroup()`:**
```php
protected function getFeaturedPublicGroup()
{
    return Group::public()
        ->active()
        ->orderBy('created_at', 'desc')
        ->first();
}
```

**Cambios en `create()`:**
- ✅ Obtener rol del usuario autenticado
- ✅ Si NO es admin, excluir "public" del select de categorías
- ✅ Pasar variable `isAdmin` a la vista

```php
$isAdmin = auth()->user()->hasRole('admin');
// Pasar a la vista
```

---

### Fase 4: Validación en GroupController
**Método `store()`:**

**Cambios:**
- ✅ Validar que solo admins creen grupos públicos
- ✅ Si categoría es "public", requerir `expires_at`
- ✅ Validar que `expires_at` sea fecha futura

```php
// En validación
'category' => 'required|in:official,amateur,public',
'expires_at' => [
    'required_if:category,public',
    'nullable',
    'date_format:Y-m-d H:i',
    'after:now'
]

// En store()
if ($validated['category'] === 'public' && !auth()->user()->hasRole('admin')) {
    abort(403, 'Solo administradores pueden crear grupos públicos');
}
```

---

### Fase 5: Vistas - Crear Grupo
**Archivo:** `resources/views/groups/create.blade.php`

**Cambios:**
- ✅ Agregar campo datetime `expires_at` (oculto si no es admin)
- ✅ Mostrar/ocultar opción "public" en select de categorías basado en `$isAdmin`
- ✅ Validación frontend: si selecciona "public", mostrar campo de expiración requerido
- ✅ Usar componente datetime-picker reutilizable

```blade
@if($isAdmin)
    <div id="expirationField" style="display: none;">
        <label>{{ __('Fecha de Expiración') }}</label>
        <input type="datetime-local" name="expires_at" id="expires_at">
    </div>
@endif

<script>
    // Mostrar/ocultar campo de expiración según categoría
    document.getElementById('category').addEventListener('change', function() {
        const expirationField = document.getElementById('expirationField');
        expirationField.style.display = this.value === 'public' ? 'block' : 'none';
        
        // Si es public, hacer requerido
        if (this.value === 'public') {
            document.getElementById('expires_at').required = true;
        } else {
            document.getElementById('expires_at').required = false;
        }
    });
</script>
```

---

### Fase 6: Vistas - Índice de Grupos
**Archivo:** `resources/views/groups/index.blade.php`

**Cambios:**
- ✅ Reemplazar componente `<x-matches.featured-match>` por `<x-groups.featured-group>`
- ✅ Cambiar variable de `$featuredMatch` a `$featuredGroup`
- ✅ Pasar `:group="$featuredGroup"` en lugar de `:match="$featuredMatch"`

```blade
{{-- ANTES --}}
@if($featuredMatch)
    <x-matches.featured-match
        :match="$featuredMatch"
        title="{{ __('views.groups.featured_match') }}"
    />
@endif

{{-- DESPUÉS --}}
@if($featuredGroup)
    <x-groups.featured-group
        :group="$featuredGroup"
        title="{{ __('views.groups.featured_public_group') }}"
    />
@endif
```

---

### Fase 7: Componente Featured Group
**Archivo:** `resources/views/components/groups/featured-group.blade.php`

**Nuevo componente similar a featured-match:**
```blade
@props([
    'group' => null,
    'title' => __('views.groups.featured_public_group')
])

@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
@endphp

@if($group)
<div class="featured-group">
    <div class="featured-title">
        <i class="fas fa-globe"></i> {{ $title }}
    </div>
    <div class="group-card" onclick="openGroupDetail({{ $group->id }})" style="cursor: pointer;">
        <div class="group-header">
            <h3>{{ $group->name }}</h3>
            <span class="badge-public">{{ __('views.groups.public') }}</span>
        </div>
        
        <div class="group-info">
            <p><strong>{{ $group->users()->count() }}</strong> {{ __('views.groups.members') }}</p>
            <p>{{ __('views.groups.created_by') }} {{ $group->creator->name }}</p>
            @if($group->expires_at)
                <p style="color: #ff9800;">
                    <i class="fas fa-clock"></i>
                    {{ __('views.groups.expires_in') }} {{ $group->expires_at->diffForHumans() }}
                </p>
            @endif
        </div>
        
        <button class="btn-join" onclick="event.stopPropagation(); joinPublicGroup({{ $group->id }})">
            {{ auth()->user()->groups->contains($group->id) ? __('views.groups.already_member') : __('views.groups.join_now') }}
        </button>
    </div>
</div>
@endif
```

---

### Fase 8: Scheduler - Auto-eliminación
**Archivo:** `app/Console/Kernel.php`

**Cambios:**
- ✅ Crear comando artisan `groups:delete-expired`
- ✅ Registrar en schedule
- ✅ Ejecutar 1x diaria (recomendado a las 00:00)

**Comando:** `app/Console/Commands/DeleteExpiredGroups.php`

```php
protected function handle()
{
    $deletedCount = Group::where('category', 'public')
        ->where('expires_at', '<', now())
        ->delete();
    
    $this->info("Eliminados {$deletedCount} grupos públicos expirados");
    
    return Command::SUCCESS;
}
```

**En Kernel.php:**
```php
$schedule->command('groups:delete-expired')->daily();
```

---

### Fase 9: Traducciones
**Archivo:** `resources/lang/es/views.php` (o archivo equivalente)

**Nuevas claves:**
```php
'groups' => [
    // ... existentes ...
    'public' => 'Público',
    'featured_public_group' => 'Grupo Público Destacado',
    'join_now' => 'Unirse ahora',
    'already_member' => 'Ya eres miembro',
    'expires_in' => 'Expira en',
    'created_by' => 'Creado por',
]
```

---

### Fase 10: Estilos CSS
**Archivo:** `resources/views/components/mobile-dark-layout.blade.php` y theme files

**Nuevas clases:**
```css
.featured-group {
    margin: 20px 15px;
    transition: all 0.3s ease;
}

.featured-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #00deb0;
}

.group-card {
    background: linear-gradient(135deg, rgba(0, 222, 176, 0.05), rgba(0, 222, 176, 0.02));
    border: 1px solid rgba(0, 222, 176, 0.2);
    border-radius: 12px;
    padding: 16px;
}

.badge-public {
    background: #00deb0;
    color: #000;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.btn-join {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #17b796, #00deb0);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}
```

---

## 📝 Secuencia de Implementación

```
1. ✅ Crear migración (agregar expires_at)
2. ✅ Actualizar modelo Group
3. ✅ Actualizar GroupController (métodos + validaciones)
4. ✅ Actualizar vista create.blade.php
5. ✅ Actualizar vista index.blade.php
6. ✅ Crear componente featured-group.blade.php
7. ✅ Agregar traducciones
8. ✅ Agregar estilos CSS
9. ✅ Crear comando DeleteExpiredGroups
10. ✅ Registrar en Kernel.php
11. ✅ Testing manual end-to-end
12. ✅ Testing permisos admin/user
```

---

## 🔒 Validaciones de Seguridad

- [x] Solo admins pueden crear grupos públicos
- [x] Solo admins pueden editar fecha de expiración
- [x] Validación: `expires_at` debe ser fecha futura
- [x] Auto-eliminación scheduled (sin dependencias de usuario)
- [x] No se puede unir a grupo expirado (verificar en `join()`)

---

## 🧪 Casos de Prueba

### Admin - Crear grupo público
- [ ] Admin ve opción "Público" en select
- [ ] Al seleccionar "Público", aparece campo de expiración
- [ ] Campo expiración es requerido
- [ ] Se valida que sea fecha futura
- [ ] Se crea grupo correctamente

### Usuario regular - Crear grupo
- [ ] Usuario NO ve opción "Público" en select
- [ ] Solo ve "official" y "amateur"

### Vista principal (index)
- [ ] Se muestra grupo público destacado (si existe)
- [ ] Botón "Unirse ahora" funciona
- [ ] Se muestra fecha de expiración
- [ ] Se reemplaza featured-match correctamente

### Auto-eliminación
- [ ] Crear grupo público con expiración en 1 hora
- [ ] Ejecutar `php artisan groups:delete-expired`
- [ ] Grupo se elimina cuando pasa expiración

---

## 📦 Dependencias
- Laravel >= 9.0
- Carbon (para fechas)
- Spatie/Laravel-permission (si ya está en uso para roles)

---

## 🚀 Rollback Strategy
Si algo falla:
1. Revertir migración: `php artisan migrate:rollback --step=1`
2. Cambiar vista index.blade.php de vuelta a featured-match
3. Eliminar comando DeleteExpiredGroups
4. Revertir cambios en GroupController

---

## 📌 Notas Adicionales

- **Campos opcionales inicialmente:** La migración crea `expires_at` nullable para no romper grupos existentes
- **Migración de datos:** No es necesaria, solo afecta nuevos grupos
- **Performance:** Agregar índice en `expires_at` y `category` para queries rápidas
- **Logs:** Considerar registrar eliminaciones automáticas en activity log

---

**Rama de desarrollo:** `feature/public-groups-with-expiration`
**Fecha de inicio:** 11 de marzo de 2026
**Prioridad:** Media
