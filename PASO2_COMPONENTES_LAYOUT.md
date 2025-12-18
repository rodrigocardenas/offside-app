# PASO 2: Componentes de Layout Comunes - Completado ✅

## 📦 Componentes Creados

### 1. Header Profile (`layout.header-profile`)
**Ubicación:** `resources/views/components/layout/header-profile.blade.php`

**Props:**
- `logoUrl` (opcional): URL del logo o imagen de perfil
- `altText` (default: 'Offside Club'): Texto alternativo para la imagen

**Uso:**
```blade
{{-- Con logo personalizado --}}
<x-layout.header-profile 
    :logo-url="asset('images/logo.png')"
    alt-text="Mi Grupo" 
/>

{{-- Sin logo (muestra iniciales) --}}
<x-layout.header-profile alt-text="Los Cracks" />
```

---

### 2. Bottom Navigation (`layout.bottom-navigation`)
**Ubicación:** `resources/views/components/layout/bottom-navigation.blade.php`

**Props:**
- `activeItem` (default: 'grupo'): Item activo del menú ('grupo', 'comunidades', 'opinion', 'perfil')

**Uso:**
```blade
{{-- En la vista de grupos --}}
<x-layout.bottom-navigation active-item="grupo" />

{{-- En la vista de comunidades --}}
<x-layout.bottom-navigation active-item="comunidades" />

{{-- En la vista de perfil --}}
<x-layout.bottom-navigation active-item="perfil" />
```

**Funcionalidades:**
- Navegación entre secciones principales
- Resaltado del item activo
- Botón de feedback integrado (abre modal)
- Iconos Font Awesome
- Responsive y fijo en la parte inferior

---

### 3. Notification Banner (`common.notification-banner`)
**Ubicación:** `resources/views/components/common/notification-banner.blade.php`

**Props:**
- `show` (default: false): Mostrar/ocultar el banner
- `message` (default: ''): Mensaje a mostrar
- `type` (default: 'warning'): Tipo de notificación ('warning', 'info', 'success', 'error')
- `icon` (opcional): Icono Font Awesome personalizado

**Uso:**
```blade
{{-- Banner de advertencia con prop message --}}
<x-common.notification-banner 
    :show="$hasPendingPredictions"
    message="Tienes predicciones pendientes en algunos grupos"
    type="warning"
/>

{{-- Banner de éxito con slot --}}
<x-common.notification-banner 
    :show="true"
    type="success"
>
    ¡Tu predicción ha sido guardada exitosamente!
</x-common.notification-banner>

{{-- Banner de info con icono personalizado --}}
<x-common.notification-banner 
    :show="$showInfo"
    type="info"
    icon="lightbulb"
>
    Tip: Puedes invitar amigos usando el código del grupo
</x-common.notification-banner>

{{-- Banner de error --}}
<x-common.notification-banner 
    :show="session('error')"
    :message="session('error')"
    type="error"
/>
```

**Estilos por tipo:**
- `warning`: Amarillo (predicciones pendientes, advertencias)
- `info`: Azul (información general, tips)
- `success`: Verde (acciones completadas)
- `error`: Rojo (errores, fallos)

---

## 🎨 JavaScript Module

### Navigation.js
**Ubicación:** `public/js/common/navigation.js`

**Funcionalidades:**
- Detecta página actual y marca item activo
- Añade feedback visual en clicks
- Función helper para notificaciones

**Funciones exportadas:**
```javascript
// Actualizar item activo manualmente
updateActiveMenuItem('grupo');

// Mostrar notificación temporal
showNavigationNotification('Acción completada', 3000);
```

**Auto-importado en:** `app-layout.blade.php`

---

## 📋 Ejemplo de Uso Completo

### Vista Ejemplo: `groups/index.blade.php`
```blade
<x-app-layout>
    <div class="min-h-screen bg-gray-100 pb-24">
        
        {{-- Header con logo --}}
        <x-layout.header-profile 
            :logo-url="asset('images/logo.png')"
            alt-text="Offside Club" 
        />
        
        {{-- Banner de notificaciones --}}
        <x-common.notification-banner 
            :show="$hasPendingPredictions"
            message="Tienes predicciones pendientes en algunos grupos"
            type="warning"
        />
        
        {{-- Contenido principal --}}
        <div class="px-4 py-6">
            <h2 class="text-xl font-semibold mb-4">Mis Grupos</h2>
            {{-- Tu contenido aquí --}}
        </div>
        
        {{-- Navegación inferior fija --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>
</x-app-layout>
```

---

## ✅ Testing

### Checklist de Verificación:
- [x] Header muestra logo correctamente
- [x] Header muestra iniciales cuando no hay logo
- [x] Bottom navigation resalta item activo
- [x] Bottom navigation navega correctamente
- [x] Notification banner muestra/oculta según prop `show`
- [x] Notification banner aplica estilos según tipo
- [x] JavaScript de navegación cargado sin errores
- [x] Responsive en mobile (414px)
- [x] Responsive en tablet/desktop

---

## 🎯 Próximo Paso

Con estos componentes base listos, podemos continuar con:

**PASO 3: Componentes de Grupos (Index)**
- Stats bar (estadísticas del usuario)
- Group card (tarjetas de grupos)
- Featured match (partido destacado)

---

## 📝 Notas Técnicas

### Rutas Requeridas
El bottom navigation requiere estas rutas definidas:
- `groups.index` - Lista de grupos
- `competitions.index` - Comunidades
- `profile.edit` - Perfil de usuario

### Dependencias
- Font Awesome 6.4.0 (ya incluido)
- Tailwind CSS con clases custom (ya configurado)
- Alpine.js o JavaScript vanilla para modals

### Compatibilidad
- ✅ Laravel 10+
- ✅ Blade components
- ✅ Tailwind CSS 3+
- ✅ Modern browsers (Chrome, Firefox, Safari, Edge)

---

**Creado:** 2025-12-15  
**Estado:** Completado ✅  
**Tiempo total:** ~1 hora
