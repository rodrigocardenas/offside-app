# 🧪 Quick Test - Deep Links de Invitación

## Test Rápido en Navegador (SIN necesidad de APK)

### Paso 1: Simular el Deep Link en Consola

Abre tu app en navegador: `https://app.offsideclub.es`

1. Ve a cualquier grupo
2. Click en "Compartir"
3. Copia el contenido del campo de texto
4. Debería mostrar:

```
¡Únete al grupo "Nombre del Grupo" en Offside Club!

offsideclub://invite/abc123xyz

¡Ven a competir con nosotros!
```

### Paso 2: Verificar Que el Web URL se Genera

1. En la consola del navegador (F12), ejecuta:

```javascript
// Obtener la modal
const textarea = document.getElementById('inviteMessage');

// Ver los data attributes
console.log('Deep Link:', textarea.dataset.deepLink);
console.log('Web URL:', textarea.dataset.webUrl);
console.log('Code:', textarea.dataset.code);
```

**Debería mostrar**:
```
Deep Link: offsideclub://invite/abc123xyz
Web URL: https://app.offsideclub.es/groups/invite/abc123xyz
Code: abc123xyz
```

### Paso 3: Verificar que WhatsApp obtiene Ambos URLs

1. Click en "WhatsApp"
2. Se abre WhatsApp con un mensaje que incluye:
   - El deep link: `offsideclub://invite/abc123xyz`
   - El web URL: `https://app.offsideclub.es/groups/invite/abc123xyz`

---

## Test Completo en Dispositivo Android (CON APK)

### Requisitos
- Dispositivo Android con app instalada
- APK debug compilado: `app-debug.apk`

### Proceso

**Paso 1: Instalar APK**
```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

**Paso 2: Abrir App**
- Abre OffsideClub desde home

**Paso 3: Probar Deep Link**
- Ve a un grupo
- Click "Compartir"
- Click "WhatsApp"
- Se abre WhatsApp con ambas URLs
- Copia el deep link: `offsideclub://invite/abc123xyz`

**Paso 4: Verificar que Funciona**

Opción A (Mismo dispositivo con app):
```
1. Pega el link en Notes/Telegram/o otro sitio
2. Click en el link
3. ✅ La app debe abrir directamente en pantalla de invitación
4. Debería ver logs: [DeepLinks] Navegando a /invite/abc123xyz
```

Opción B (Otro dispositivo con app):
```
1. Envía el link por WhatsApp
2. En otro dispositivo (con app instalada)
3. Click en el link
4. ✅ La app abre en pantalla de invitación
```

Opción C (Dispositivo sin app):
```
1. Envía el link
2. Dispositivo sin app instalada
3. Click en el link
4. Android intenta abrir offsideclub://
5. No encuentra app, fallback a web
6. Copiar y pegar el web URL en navegador
7. ✅ Se abre en navegador web
```

---

## Logs en Dispositivo

Ejecuta en terminal:
```bash
adb logcat | grep -E "DeepLinks|showInviteModal|shareOnWhatsApp"
```

**Cuando abres la modal de invitación, debería ver**:
```
[DeepLinks] Handler inicializado correctamente
```

**Cuando haces click en el deep link desde otro sitio**:
```
[DeepLinks] Deep link detectado: offsideclub://invite/abc123xyz
[DeepLinks] URL parseada: host = invite, path = abc123xyz
[DeepLinks] Navegando a /invite/abc123xyz
```

---

## Checklist de Validación

### En Navegador Web ✅
- [ ] Grupo muestra botón "Compartir"
- [ ] Modal aparece al hacer click
- [ ] Modal muestra deep link: `offsideclub://invite/...`
- [ ] Console muestra data attributes:
  - `deepLink`: offsideclub://invite/...
  - `webUrl`: https://app.offsideclub.es/groups/invite/...
  - `code`: ...
- [ ] Botón "Copiar" funciona
- [ ] Botón "WhatsApp" abre WhatsApp con mensaje

### En Dispositivo (con app) 🔄
- [ ] APK instalado correctamente
- [ ] App abre sin errores
- [ ] Grupo muestra botón "Compartir"
- [ ] Modal aparece con deep link
- [ ] WhatsApp abre con ambas URLs
- [ ] Click en deep link desde otro sitio abre app
- [ ] App navega a pantalla de invitación
- [ ] Logs muestran `[DeepLinks] Navegando a /invite/...`
- [ ] Botón "Unirme" une al usuario al grupo

### En Dispositivo (sin app) 📱
- [ ] Click en deep link intenta abrir app
- [ ] Android fallback a web
- [ ] Web URL funciona en navegador
- [ ] Usuario puede aceptar invitación

---

## Troubleshooting

### Modal NO muestra deep link

**Posible causa**: APK vieja o compilación incompleta

**Solución**:
```bash
# Recompilar
npm run build
npx cap sync android
cd android && ./gradlew clean assembleDebug
adb uninstall com.offsideclub.app
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

### Deep link NO abre la app

**Posible causa**: Intent-filter no configurado o APK vieja

**Solución**:
```bash
# Ver logs
adb logcat | grep DeepLinks

# Si no aparecen logs, significa que DeepLinksHandler no cargó
# Reinstalar APK nueva
```

### WhatsApp NO muestra el link correcto

**Posible causa**: Modal no guardó data attributes

**Solución**:
```javascript
// En console verificar
document.getElementById('inviteMessage').dataset
// Debería mostrar: { deepLink, webUrl, code }
```

---

## Resumen Rápido

**¿Qué cambió?**
- Modal ahora genera `offsideclub://invite/{code}` (deep link)
- Compartir en WhatsApp envía ambas URLs:
  - Deep link (si tienes app)
  - Web URL (si no tienes app)

**¿Por qué?**
- Máxima compatibilidad
- Mejor UX si tienes app

**¿Cómo pruebo?**
1. Web: F12 console → Ver data attributes
2. Dispositivo: ADB → Instalar APK → Probar link

---

**¿Necesitas ayuda con el testing?** 📞

Pídeme que:
1. Debuguee con logs
2. Recompile APK
3. Revise los data attributes
4. Verifique AndroidManifest
