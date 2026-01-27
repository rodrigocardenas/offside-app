# Deep Links Implementation - Bug #2

## Resumen
Se implementó soporte para Deep Links en la app móvil Capacitor. Ahora los links como `offsideclub://group/123` abren la app móvil en lugar del navegador web.

## Cambios Realizados

### 1. Handler JavaScript
**Archivo**: `resources/js/deep-links.js`
- Nueva clase `DeepLinksHandler`
- Detecta Capacitor en tiempo de ejecución
- Escucha eventos `App.addListener('appUrlOpen')`
- Parsea URLs con esquema `offsideclub://`
- Soporta rutas: 
  - `offsideclub://group/{id}` → `/groups/{id}`
  - `offsideclub://match/{id}` → `/matches/{id}`
  - `offsideclub://profile/{id}` → `/profile/{id}`
  - `offsideclub://invite/{token}` → `/invite/{token}`
- Incluye logging con prefix `[DeepLinks]`

### 2. Integración en App
**Archivo**: `resources/js/app.js`
- Importado handler: `import './deep-links';`
- Se ejecuta automáticamente en tiempo de carga

### 3. Intent Filter en Android
**Archivo**: `android/app/src/main/AndroidManifest.xml`
```xml
<!-- Deep link intent filter para offsideclub:// scheme -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="offsideclub" />
</intent-filter>
```

### 4. Sincronización Capacitor
Ejecutado:
```bash
npm run build              # Compila assets con Vite
npx cap sync android      # Sincroniza con Android
```

## Flujo de Funcionamiento

1. **Desde navegador web**: Usuario hace clic en `<a href="offsideclub://group/123">Abrir en app</a>`
2. **Android intercepta**: Intent filter captura el esquema `offsideclub://`
3. **Abre Capacitor**: MainActivity inicia con URL
4. **Handler ejecuta**: `AppUrlOpen` event dispara `handleDeepLink()`
5. **Navega**: URL parseada → navegación interna via `window.location.href`
6. **Interfaz actualiza**: Vue/Alpine actualiza vista con ID recibido

## Testing

### En Desarrollo (Web)
```javascript
// Simular deep link en consola
window.location.href = 'offsideclub://group/123';
```

### En Dispositivo Android
1. Click en link de invitación
2. "Abrir con..." → Selecciona "OffsideClub"
3. App abre directo al grupo/partido/invitación

## Dependencias
- `@capacitor/app@6.0.3` - Proporciona App plugin con evento `appUrlOpen`
- `@capacitor/app-launcher@8.0.0` - Para gestión avanzada de URLs (instalado pero no usado aún)

## Próximos Pasos
1. ✅ Compilar APK con cambios
2. ✅ Instalar en dispositivo de testing
3. ⏳ Probar links de invitación en dispositivo real
4. ⏳ Verificar navegación correcta a grupo/partido
5. ⏳ Ajustar rutas si es necesario

## Notas Técnicas
- El handler se inicializa automáticamente cuando carga `app.js`
- Si no está en Capacitor (web), simplemente skips sin error
- URLs parseadas con `new URL()` para compatibilidad
- Logging incluido para debugging en dispositivo real

## Estado
🟡 **En Progreso** - Código implementado, APK compilándose, pendiente testing en dispositivo
