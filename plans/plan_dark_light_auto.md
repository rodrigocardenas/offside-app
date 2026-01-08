# Plan de Implementación de Modo Oscuro/Claro Automático

## Objetivo
Implementar la funcionalidad para que la aplicación se muestre en modo oscuro o claro según la configuración del dispositivo del usuario, cuando el usuario tenga seleccionado "auto" en sus preferencias de tema.

## Estado Actual
- La aplicación ya tiene soporte para temas con `theme_mode` en el modelo User: 'light', 'dark', 'auto'
- El middleware `ApplyTheme` maneja la lógica de temas, pero actualmente 'auto' default a oscuro
- Los temas se aplican mediante variables compartidas en vistas y clases CSS custom

## Pasos de Implementación

### 1. Actualizar Sistema de Temas
- **Cambiar de variables PHP a CSS Custom Properties**: Reemplazar el uso de variables PHP inline por CSS variables para permitir cambios dinámicos con JavaScript
- **Actualizar ApplyTheme middleware**: Modificar para establecer CSS variables iniciales en lugar de variables PHP
- **Crear sistema de clases CSS**: Usar data-theme attribute en <html> para controlar temas

### 2. Implementar Detección de Preferencia del Dispositivo
- **JavaScript para detectar prefers-color-scheme**: Crear script que detecte la preferencia del sistema operativo
- **Lógica para modo 'auto'**: Cuando theme_mode es 'auto', usar la preferencia del dispositivo
- **Lógica para modos 'light'/'dark'**: Forzar el tema seleccionado independientemente de la preferencia del dispositivo

### 3. Actualizar Archivos CSS
- **tailwind.config.js**: Definir CSS variables para colores en lugar de valores fijos
- **resources/css/app.css**: Agregar reglas CSS con media queries para prefers-color-scheme
- **Crear tema base**: Definir variables CSS para light y dark

### 4. Modificar Vistas y Componentes
- **Quitar variables PHP inline**: Reemplazar {{ $bgPrimary }} por clases CSS que usen variables
- **Actualizar layouts**: Agregar data-theme attribute y scripts de detección
- **Revisar componentes**: Asegurar que todos usen el nuevo sistema de variables CSS

### 5. JavaScript para Manejo Dinámico
- **Script de inicialización**: Detectar tema inicial basado en user preferences y device
- **Observer para cambios**: Escuchar cambios en prefers-color-scheme para actualizar automáticamente
- **Función de cambio de tema**: Permitir cambios dinámicos sin recargar página

### 6. Actualizar Settings
- **UI de configuración**: Mejorar la interfaz para explicar el comportamiento de 'auto'
- **Persistencia**: Asegurar que la selección se guarde correctamente

## Archivos a Modificar

### Backend
- `app/Http/Middleware/ApplyTheme.php` - Cambiar lógica de aplicación de temas
- `app/Http/Controllers/SettingsController.php` - Posiblemente actualizar validación

### Frontend - CSS
- `tailwind.config.js` - Definir variables CSS
- `resources/css/app.css` - Agregar reglas de tema
- `resources/css/components.css` - Actualizar si usa colores custom

### Frontend - JavaScript
- `resources/js/app.js` - Agregar script de detección de tema
- Crear nuevo archivo `resources/js/theme.js` - Lógica de manejo de temas

### Vistas
- `layouts/app.blade.php` - Agregar data-theme y scripts
- Todas las vistas que usan variables de tema (dashboard, groups, etc.)
- Componentes en `resources/views/components/`

## Ejemplo de Implementación

### CSS Variables (tailwind.config.js)
```js
theme: {
  extend: {
    colors: {
      'bg-primary': 'var(--bg-primary)',
      'bg-secondary': 'var(--bg-secondary)',
      // ... otros colores
    }
  }
}
```

### CSS Rules (app.css)
```css
:root {
  --bg-primary: #1a1a1a; /* dark default */
  --bg-secondary: #2a2a2a;
  /* ... */
}

@media (prefers-color-scheme: light) {
  :root {
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    /* ... */
  }
}

[data-theme="light"] {
  --bg-primary: #ffffff;
  --bg-secondary: #f8f9fa;
}

[data-theme="dark"] {
  --bg-primary: #1a1a1a;
  --bg-secondary: #2a2a2a;
}
```

### JavaScript (theme.js)
```js
function applyTheme(themeMode, systemPreference) {
  const html = document.documentElement;
  
  if (themeMode === 'auto') {
    html.setAttribute('data-theme', systemPreference);
  } else {
    html.setAttribute('data-theme', themeMode);
  }
}

// Detectar preferencia del sistema
const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

// Aplicar tema inicial
applyTheme('{{ $userThemeMode }}', systemPreference);

// Escuchar cambios
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
  if ('{{ $userThemeMode }}' === 'auto') {
    applyTheme('auto', e.matches ? 'dark' : 'light');
  }
});
```

## Consideraciones
- **Performance**: Minimizar flash de contenido sin estilo (FOUC)
- **Compatibilidad**: Asegurar soporte en navegadores antiguos
- **PWA**: Considerar tema en service worker y manifest
- **Testing**: Probar en diferentes dispositivos y configuraciones

## Próximos Pasos
1. Actualizar sistema CSS a variables
2. Implementar detección JavaScript
3. Modificar middleware
4. Actualizar vistas gradualmente
5. Testing exhaustivo
