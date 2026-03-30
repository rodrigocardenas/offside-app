# Guía de Toasts Modernos - Offside Club

## ¿Qué cambió?

Hemos reemplazado el sistema de alertas `alert()` nativo por **Toastify JS**, una librería moderna, responsive y con mucho mejor diseño visual.

## Características

✅ **Moderno y responsive** - Se adapta a todos los tamaños de pantalla  
✅ **Animaciones suaves** - Entrada y salida elegantes  
✅ **No intrusivo** - Aparece en la esquina sin bloquear la aplicación  
✅ **Auto-cierre** - Se cierra automáticamente después del tiempo configurado  
✅ **Posible cerrar manualmente** - Botón X para cerrar antes  

## Cómo usar

### Desde JavaScript

#### Success (Éxito)
```javascript
window.showSuccessToast('¡Operación exitosa!')
```

#### Error
```javascript
window.showErrorToast('Ocurrió un error. Intenta nuevamente.')
```

#### Info
```javascript
window.showInfoToast('Aquí va la información importante')
```

#### Warning (Advertencia)
```javascript
window.showWarningToast('Ten cuidado, esto es importante')
```

#### Con duración personalizada
```javascript
window.showToast('Mi mensaje', 'success', 5000) // Dura 5 segundos
```

### Desde Blade (Laravel)

Simplemente haz redirect con un mensaje de sesión y automáticamente se convierte en toast:

```php
// En tu controlador
return redirect()->route('grupos.show', $group)
    ->with('success', '¡Grupo creado exitosamente!');

return redirect()->back()
    ->with('error', 'Ya existe un grupo con este nombre');

return redirect()->back()
    ->with('warning', 'Cambios no guardados');

return redirect()->back()
    ->with('info', 'Tu perfil está incompleto');
```

### Desde formularios
```blade
<!-- Si hay errores de validación, se mostrarán automáticamente como toasts -->
@if($errors->any())
    <!-- Los errores se convierten a toasts automáticamente -->
@endif
```

## Tipos de Toast

| Tipo | Uso | Color |
|------|-----|-------|
| **success** | Operaciones exitosas | Verde |
| **error** | Errores o fallos | Rojo |
| **info** | Información general | Azul |
| **warning** | Advertencias importantes | Naranja |

## Duración por defecto

- Success: 3 segundos
- Error: 4 segundos (más tiempo para leer)
- Info: 3 segundos
- Warning: 3.5 segundos

## Ejemplos reales en el proyecto

### Envío de formulario exitoso
```javascript
fetch('/api/profile', {
    method: 'POST',
    body: formData
})
.then(response => {
    window.showSuccessToast('Perfil actualizado');
})
.catch(error => {
    window.showErrorToast('Error al actualizar el perfil');
});
```

### Validación en tiempo real
```javascript
if (!emailValid) {
    window.showWarningToast('El email no es válido');
    return;
}
```

### Respuesta del servidor
```php
// En el controlador
if ($success) {
    return redirect()->back()->with('success', 'Acción completada');
} else {
    return redirect()->back()->with('error', 'Algo salió mal');
}
```

## Responsive

Los toasts se adaptan automáticamente:
- **Desktop**: Aparecen en la esquina superior derecha
- **Tablet**: Ancho máximo de 420px
- **Mobile**: Ocupan casi todo el ancho (con márgenes)

## Estilo personalizado

Los toasts incluyen:
- Gradientes de colores atractivos
- Iconos automáticos (✓, ✕, ℹ, ⚠)
- Sombra suave con blur
- Animaciones slide-in/slide-out
- Bordes semi-transparentes

## Archivos modificados

- ✅ `resources/js/toast-helper.js` - Funciones helper
- ✅ `resources/views/components/common/toast-messages.blade.php` - Componente de sesión
- ✅ `public/css/toasts.css` - Estilos personalizados
- ✅ `resources/js/app.js` - Import del helper
- ✅ `resources/views/layouts/app.blade.php` - Incluye CSS y componente
- ✅ Archivos con alerts() reemplazados por toasts

## Dependencias

- `toastify-js` - Librería de toasts
- Alpine.js - Ya estaba
- Tailwind CSS - Ya estaba

¡Disfruta de los nuevos toasts modernos! 🎉
