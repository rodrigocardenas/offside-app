# Bugs Móviles - Estado de Implementación

## Resumen Ejecutivo
Se han implementado soluciones para 3 bugs críticos de la app móvil Android:
- ✅ Bug #1: Android Back Button - **COMPLETADO Y FUNCIONANDO**
- 🟡 Bug #5: Pull-to-Refresh - **COMPILADO, EN TESTING**
- 🟡 Bug #2: Deep Links - **COMPILANDO APK**

---

## Bug #1: Android Back Button 🟢 COMPLETADO

### Descripción del Problema
No funcionaba el botón atrás nativo de Android. Usuario presionaba y la app cerraba.

### Causa Raíz
1. Handler creado en `public/js/` que NO se compilaba con Vite
2. Plugin `@capacitor/app` no estaba instalado
3. Capacitor detection usaba `isNativePlatform()` que no existe

### Solución Implementada
1. Migrado handler a `resources/js/android-back-button.js`
2. Instalado `@capacitor/app@6.0.3`
3. Simplificado detection: `typeof window.Capacitor !== 'undefined'`
4. Implementado: `App.addListener('backButton')` → `history.back()`

### Estado: ✅ FUNCIONANDO
- Usuario confirmó: "sii, ahora funciona"
- Logs verifican carga correcta
- APK v1.02 genera correctamente

---

## Bug #5: Pull-to-Refresh 🟡 EN TESTING

### Descripción del Problema  
Gesto de "arrastrar desde arriba" para recargar la página no funciona en app móvil.

### Causa Raíz
Handler en `public/js/` no compilado con Vite.

### Solución Implementada
1. Migrado handler a `resources/js/pull-to-refresh.js`
2. Importado en `resources/js/app.js`
3. Mantiene implementación original:
   - Detección de gesto táctil
   - Indicador visual (barra gradiente)
   - Llamada a `/api/cache/clear-user` o reload

### Archivos
- **recursos/js/pull-to-refresh.js** (88 líneas)
  - Clase: `OffsidePullToRefresh`
  - Touch events para detección
  - Threshold: 80px para trigger

### Estado: 🟡 COMPILADO
- Código listo
- APK generado con cambios
- **Pendiente**: Testing en dispositivo real

---

## Bug #2: Deep Links 🟡 COMPILANDO

### Descripción del Problema
Links de invitación como `offsideclub://group/123` abren en navegador web en lugar de app móvil.

### Causa Raíz
- No hay intent-filter en AndroidManifest para esquema `offsideclub://`
- No hay handler JavaScript para evento `appUrlOpen`

### Solución Implementada

#### A. Código JavaScript
**Archivo**: `resources/js/deep-links.js` (116 líneas)
- Clase: `DeepLinksHandler`
- Detecta Capacitor
- Escucha: `App.addListener('appUrlOpen', event => {})`
- Parsea URLs: `offsideclub://group/123` → `/groups/123`
- Navega: `window.location.href = '/groups/123'`
- Rutas soportadas:
  - `/groups/{id}` - Grupo de inversores
  - `/matches/{id}` - Partido/fixture
  - `/profile/{id}` - Perfil de usuario
  - `/invite/{token}` - Token de invitación

#### B. AndroidManifest.xml
```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="offsideclub" />
</intent-filter>
```

#### C. Integración en App
- Importado en `resources/js/app.js`
- Se ejecuta automáticamente al cargar

### Dependencias
- `@capacitor/app@6.0.3` ✅
- `@capacitor/app-launcher@6.0.4` ✅ (downgrade por compatibilidad Java)

### Estado: 🟡 COMPILANDO
- Código: ✅ Completamente implementado
- Intent-filter: ✅ Agregado
- Build: 🔄 En progreso (gradle compiling)
- **Próximo paso**: Instalar en dispositivo, probar links

---

## Cambios Técnicos Realizados

### Estructura de Archivos
```
resources/js/
├── app.js                      (modificado - agregadas 3 importaciones)
├── android-back-button.js      (nuevo)
├── pull-to-refresh.js          (nuevo)
└── deep-links.js               (nuevo)

android/app/src/main/
└── AndroidManifest.xml         (modificado - intent-filter para deep links)

public/js/                       (deprecated)
├── android-back-button.js      (superseded)
└── pull-to-refresh.js          (superseded)
```

### Cambios en Dependencias
```json
{
  "@capacitor/app": "^6.0.3",           // ✅ Instalado para eventos nativos
  "@capacitor/app-launcher": "^6.0.4",  // ✅ Instalado, downgrade a v6 por Java
}
```

### Proceso de Compilación
```bash
npm run build               # Vite compila assets
npx cap sync android       # Sincroniza assets y plugins
./gradlew assembleDebug    # Compila APK
# Resultado: android/app/build/outputs/apk/debug/app-debug.apk
```

---

## Testing Pendiente

### Bug #5: Pull-to-Refresh
- [ ] Instalar APK en dispositivo
- [ ] Pull desde arriba en página
- [ ] Verificar loader/indicador
- [ ] Confirmar cache clear o reload

### Bug #2: Deep Links
- [ ] Instalar APK en dispositivo
- [ ] Generar link de invitación: `offsideclub://group/123`
- [ ] Compartir por chat/SMS/email
- [ ] Click abre app ✓
- [ ] Navega a grupo específico ✓
- [ ] Probar otros esquemas: match, profile, invite

---

## Próximos Pasos Inmediatos

1. **Esperar build**: Gradle compilando APK (~1-2 min)
2. **Generar APK final**: Una vez completado, tendrá:
   - ✅ Android back button funcionando
   - ✅ Pull-to-refresh funcionando
   - ✅ Deep links funcionando
3. **Instalar en dispositivo de testing**
4. **Testing en vivo**: Verificar cada bug
5. **Iteración si es necesario**: Ajustar rutas de deep links, etc.
6. **Deployment**: Play Store con versión nueva

---

## Notas Técnicas

### Detección Capacitor
```javascript
// ✅ Funciona
typeof window.Capacitor !== 'undefined'

// ❌ No funciona (deprecated/inexistente)
window.Capacitor.isNativePlatform()
```

### Pipeline de Actualización APK
1. Código en `resources/js/`
2. Vite compila a `public/build/`
3. Capacitor copia a `android/app/src/main/assets/public`
4. APK incluye archivos compilados
5. Usuario actualiza desde Play Store o ADB

### Eventos Capacitor Utilizados
- **BackButton**: `App.addListener('backButton')`
- **AppUrlOpen**: `App.addListener('appUrlOpen')`

---

## Estado de Versiones

| Componente | Versión | Estado |
|-----------|---------|--------|
| Capacitor | 6.2.1 | ✅ |
| @capacitor/app | 6.0.3 | ✅ |
| @capacitor/app-launcher | 6.0.4 | ✅ |
| Vite | 5.4.18 | ✅ |
| Laravel | 11 | ✅ |
| Node | 20.19.0 | ✅ |
| Java | 20.0.2 | ✅ |
| Gradle | 8.11.1 | ✅ |

---

## Última Actualización
Implementación completa de todos los handlers. APK en compilación. Pendiente testing en dispositivo.
