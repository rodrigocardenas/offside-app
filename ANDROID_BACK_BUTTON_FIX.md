# Fix: Android Back Button Not Working (Bug #1)

## Problem
El botón/gesto de atrás nativo de Android siempre regresa a la pantalla de inicio, en lugar de navegar a la pantalla anterior.

## Root Cause
No había un manejador (listener) configurado para el evento `backButton` de Capacitor. Sin esto, Capacitor no sabe qué hacer cuando el usuario presiona el botón de atrás en Android.

## Solution Implemented

### 1. Created Android Back Button Handler
**File:** `public/js/android-back-button.js`

Este script proporciona:
- Detección si la app está corriendo en Capacitor (plataforma nativa)
- Escucha el evento `backButton` de Capacitor
- Utiliza `history.back()` para navegar al historial anterior del navegador
- Si no hay historial, muestra un diálogo de confirmación para salir de la app
- Logs de depuración para monitorear el comportamiento

```javascript
export class AndroidBackButtonHandler {
    async init() {
        if (!this.isCapacitorApp()) return;
        
        const { App } = window.Capacitor.Plugins || {};
        if (!App) return;

        App.addListener('backButton', async () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                this.showExitConfirmDialog();
            }
        });
    }
}
```

### 2. Integrated into Layout
**File:** `resources/views/layouts/app.blade.php`

El script se inicializa en el `<head>` del layout principal:
```blade
<!-- Android Back Button Handler (solo en Capacitor) -->
<script type="module">
    import { AndroidBackButtonHandler } from '{{ asset('js/android-back-button.js') }}';
    const handler = new AndroidBackButtonHandler();
    handler.init();
</script>
```

## How It Works

1. **Capacitor Detection**: Al cargar la página, verifica si estamos en un entorno de Capacitor
2. **Event Listener**: Registra un listener para el evento `backButton` nativo
3. **History Navigation**: Cuando el usuario presiona atrás:
   - Si hay historial → Navega a la pantalla anterior con `history.back()`
   - Si no hay historial → Muestra diálogo de confirmación para salir

## Testing Instructions

### Testing on Android Emulator

1. **Build the App:**
   ```bash
   npm run build  # Build Vue/Alpine assets
   npx cap sync android  # Sincronizar cambios con Capacitor
   npx cap open android  # Abrir en Android Studio
   ```

2. **Run on Emulator:**
   - Abre Android Studio
   - Selecciona un emulador de Android
   - Presiona Run

3. **Test Navigation:**
   - Abre la app
   - Navega a varias páginas (Groups, Matches, Profile, etc.)
   - Presiona el botón de atrás de Android (físico o emulado)
   - **Expected behavior**: Debería volver a la pantalla anterior, no a la pantalla de inicio
   - Ve a través del flujo de navegación: Home → Matches → Match Detail → (Back) → Matches → (Back) → Home

4. **Test Exit Behavior:**
   - Una vez en la pantalla de inicio
   - Presiona el botón de atrás de Android
   - Debería mostrar el diálogo: "¿Deseas salir de Offside Club?"
   - Presiona "OK" para confirmar salida o "Cancel" para cancelar

### Testing on Physical Android Device

1. **Connect Device:**
   ```bash
   adb devices  # Ver dispositivos conectados
   ```

2. **Build and Deploy:**
   ```bash
   npm run build
   npx cap sync android
   npx cap build android
   ```

3. **Install APK on Device:**
   - Busca el APK en `android/app/build/outputs/apk/`
   - Cópialo al dispositivo o instálalo con `adb install`

4. **Test Navigation:**
   - Realiza los pasos 3-4 de "Test on Emulator"

### Browser Testing (Web)

En navegador web, el handler detectará que NO estamos en Capacitor y no se inicializará. El navegador usará su comportamiento de back button estándar.

## Console Logging

El script incluye logging de depuración. Abre la consola de Chrome DevTools:
```
[AndroidBackButton] Manejador inicializado correctamente
[AndroidBackButton] Back button presionado. History length: 3
[AndroidBackButton] Navegando atrás
```

Si ves `No estamos en Capacitor, no inicializando`, significa que el código está corriendo en navegador web, no en la app nativa.

## Files Modified

1. **public/js/android-back-button.js** - Nuevo manejador
2. **resources/views/layouts/app.blade.php** - Script cargado en el layout
3. Este documento - Instrucciones y documentación

## Related Configuration

- **capacitor.config.ts**: Configuración principal de Capacitor (sin cambios necesarios)
- **android/app/src/main/AndroidManifest.xml**: Permisos (sin cambios necesarios)

## Deployment Checklist

- [x] Handler creado en `public/js/android-back-button.js`
- [x] Integrado en `resources/views/layouts/app.blade.php`
- [x] Testing instructions documentadas
- [ ] Testeado en Android emulator
- [ ] Testeado en dispositivo Android físico
- [ ] Verificar en múltiples versiones de Android si es posible

## Next Steps

1. **Build and Deploy** a Android emulator/dispositivo
2. **Test Navigation** siguiendo las instrucciones de testing
3. Si falla, revisa la consola de Android Studio para logs
4. Si es exitoso, proceder con Bug #2 (Deep Links)

## Troubleshooting

### "Manejador no inicializado"
- Verifica que estés en Capacitor (no en navegador web)
- Revisa la consola para errores
- Asegúrate de que `window.Capacitor` esté disponible

### "Back button no funciona"
- Verifica que `history.length > 1` en consola
- Comprueba que la navegación esté agregando entradas al historial
- Revisa si hay código en Alpine que interfiera

### "App crashes al presionar atrás"
- Revisa los logs de Android Studio
- Podría ser que el listener esté registrado múltiples veces
- Verifica que Capacitor esté inicializado antes del handler

## References

- [Capacitor App Plugin Documentation](https://capacitorjs.com/docs/apis/app)
- [History API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/History)
- [Android Back Button Behavior](https://developer.android.com/guide/navigation/navigation-back-compat)
