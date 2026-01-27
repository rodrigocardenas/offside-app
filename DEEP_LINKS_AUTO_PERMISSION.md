# Auto-Request Deep Links Handler - Documentación

## ¿Qué es?

Ahora la app **automáticamente solicita** a los nuevos usuarios que configuren OffsideClub como handler preferido para abrir links en la primera apertura.

## Características

### ✅ Automático
- Se ejecuta automáticamente al instalar y abrir la app por primera vez
- No molesta a usuarios que ya lo tienen configurado (se detecta con localStorage)

### ✅ Smart
- En **Android 12+**: Abre directamente la pantalla de Settings
- En **Android < 12**: Muestra instrucciones paso a paso
- Compatible con todas las versiones de Android

### ✅ No-Invasivo
- Solo se muestra UNA VEZ (se guarda en localStorage)
- Usuario puede saltarlo ("Más Tarde")
- UI nativa y elegante

## Cómo funciona

### 1. Primera Apertura
```javascript
// deep-links.js detecta:
- Si es Android
- Si es la primera vez (localStorage.getItem('offsideclub_deep_links_permission_requested'))
- Si Capacitor está listo
```

### 2. Dialog
Se muestra un dialog elegante:
```
⚙️ Configuración Recomendada

Para que los links de invitación se abran correctamente 
en OffsideClub, configura esta app como handler preferido 
para nuestro dominio.

[Más Tarde] [Continuar]
```

### 3. Acción
Si el usuario hace clic en "Continuar":

**Android 12+ (API 31+):**
```
android-app://com.android.settings/action/app_open_by_default_settings
```
Se abre directamente la pantalla de handlers preferidos.

**Android < 12:**
```
android-app://com.android.settings
```
Se abre Settings general y se muestran instrucciones.

## Técnicamente

### Archivo: `resources/js/deep-links.js`
- Función `requestDeepLinksPermission()`: Verifica y solicita permiso
- Función `showDeepLinksDialog()`: Muestra el dialog
- Función `openDeepLinksSettings()`: Abre Settings
- Función `showManualInstructions()`: Instrucciones fallback

### Archivo: `resources/js/deep-links-permission.ts`
- Clase `DeepLinksPermissionHandler` (deprecated, reemplazada por funciones en deep-links.js)
- Métodos auxiliares para testing

## Testing

### Para Testing, resetear el estado:
```javascript
// En consola del browser
localStorage.removeItem('offsideclub_deep_links_permission_requested');
// Recargar la app
```

### O marcar como completado:
```javascript
localStorage.setItem('offsideclub_deep_links_permission_requested', 'true');
```

## Flujo Completo de Usuario

1. **Instalación**: Usuario descarga e instala OffsideClub
2. **Primera Apertura**: 
   - App se inicia
   - Script detecta primera vez en Android
   - Muestra dialog después de 2 segundos
3. **Usuario elige**:
   - "Más Tarde": Cierra el dialog (se volverá a mostrar en la próxima sesión... no, está guardado)
   - "Continuar": Abre Settings
4. **Configuración**: Usuario configura OffsideClub como handler
5. **Deep Links Funcionan**: Todos los links ahora se abren en la app ✅

## Beneficios

| Antes | Después |
|-------|---------|
| ❌ Links abrían en Chrome | ✅ Links abren en OffsideClub |
| ❌ Usuarios confundidos | ✅ Dialog automático |
| ❌ Soporte necesario | ✅ Auto-configuración |
| ❌ Experiencia inconsistente | ✅ Experiencia perfecta |

## Changelog

- **v1.077**: Agregada solicitud automática de permisos de deep links
- Detecta Android automáticamente
- Compatible con Android 8+
- Dialog elegante con instrucciones fallback

## FAQ

### ¿Se muestra cada vez que abro la app?
No, solo una vez. Se guarda en `localStorage` con clave `'offsideclub_deep_links_permission_requested'`.

### ¿Puedo resetear esto para testing?
Sí, ejecuta en consola:
```javascript
localStorage.removeItem('offsideclub_deep_links_permission_requested');
```

### ¿Funciona en iOS?
No, iOS maneja deep links diferente y requiere configuración a nivel de build/provisioning.

### ¿Qué pasa si el usuario hace "Más Tarde"?
Se cierra el dialog. Dado que está guardado en localStorage, no se volverá a mostrar.

Si quieres que se repita, puedes cambiar la lógica para que solo ignore si pasaron X días:
```javascript
const lastShown = localStorage.getItem('offsideclub_deep_links_permission_shown');
const daysPassed = (Date.now() - parseInt(lastShown)) / (1000 * 60 * 60 * 24);
if (daysPassed > 7) {
  // Mostrar de nuevo
}
```

### ¿Cómo desabilito esto?
Simplemente elimina la llamada a `requestDeepLinksPermission()` en `deep-links.js`.
