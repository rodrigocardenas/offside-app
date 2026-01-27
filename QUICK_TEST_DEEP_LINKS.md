# 🧪 Quick Test - Deep Links Clickeables en WhatsApp

## ✨ Cambio Principal
**ANTES**: `offsideclub://invite/abc123xyz` (NO clickeable)  
**AHORA**: `https://app.offsideclub.es/invite/abc123xyz` (✅ CLICKEABLE)

---

## Test Rápido en Navegador (SIN necesidad de APK)

### Paso 1: Abrir Modal de Invitación

1. Abre: `https://app.offsideclub.es`
2. Ve a cualquier grupo
3. Click en "Compartir"
4. Debería mostrar:

```
¡Únete al grupo "Nombre del Grupo" en Offside Club!

https://app.offsideclub.es/invite/gYjxGZ

¡Ven a competir con nosotros!
```

### Paso 2: Verificar que es Clickeable

1. En la consola del navegador (F12), ejecuta:

```javascript
// Obtener la modal
const textarea = document.getElementById('inviteMessage');

// Ver el contenido
console.log('URL en modal:', textarea.value);

// Ver los data attributes
console.log('Invite URL:', textarea.dataset.inviteUrl);
console.log('Code:', textarea.dataset.code);
```

**Debería mostrar**:
```
URL en modal: ¡Únete al grupo...https://app.offsideclub.es/invite/gYjxGZ...
Invite URL: https://app.offsideclub.es/invite/gYjxGZ
Code: gYjxGZ
```

### Paso 3: Probar en Navegador

1. Copia el link: `https://app.offsideclub.es/invite/gYjxGZ`
2. Pégalo en la barra de direcciones
3. ✅ Debería abrir la página de invitación
4. Debería ver: "Invitación a grupo X" con botón "Unirme"

---

## Test Compartir en WhatsApp (AHORA MISMO)

### En Desktop/Web
```
1. Abre: https://app.offsideclub.es
2. Ve a grupo → Click "Compartir"
3. Click "WhatsApp"
4. Se abre WhatsApp Web/Desktop
5. Mensaje con URL clickeable: https://app.offsideclub.es/invite/gYjxGZ
6. Copia o envía el mensaje
```

### En Móvil (Recibidor)
```
1. Recibe el mensaje en WhatsApp
2. ✅ El link es AZUL y CLICKEABLE
3. Click en el link
   
   CON APP:
   - Abre OffsideClub automáticamente
   - Muestra pantalla de invitación
   - Click "Unirme" → Se une al grupo ✅
   
   SIN APP:
   - Se abre Chrome/Firefox
   - Muestra pantalla de invitación
   - Click "Unirme" → Se une al grupo ✅
```

---

## Test Completo en Dispositivo Android (CON APK)

### Requisitos
- Dispositivo Android con app instalada (opcional)
- APK debug compilado: `app-debug.apk`

### Proceso

**Paso 1: Instalar APK** (Si quieres probar)
```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

**Paso 2: Probar en Navegador**
```
1. Abre Chrome en móvil: https://app.offsideclub.es
2. Ve a grupo → Click "Compartir" → Click "WhatsApp"
3. Se abre WhatsApp con el URL clickeable
4. Envía el mensaje
```

**Paso 3: Recibir y Hacer Click**
```
1. En otro dispositivo o chat, haz click en el URL
2. Opción A (CON app instalada):
   - ✅ Abre automáticamente en OffsideClub
   - ✅ Muestra pantalla de invitación
   - ✅ Click "Unirme" funciona
   
3. Opción B (SIN app instalada):
   - ✅ Se abre en navegador
   - ✅ Muestra pantalla de invitación
   - ✅ Click "Unirme" funciona
```

---

## Logs en Dispositivo (Opcional)

Ejecuta en terminal:
```bash
adb logcat | grep -E "DeepLinks|/invite"
```

**Si el deep link se activa, verías**:
```
[DeepLinks] Handler inicializado correctamente
[DeepLinks] Deep link detectado: https://app.offsideclub.es/invite/gYjxGZ
[DeepLinks] Navegando a /invite/gYjxGZ
```

---

## Checklist de Validación

### En Navegador Web ✅
- [x] Grupo muestra botón "Compartir"
- [x] Modal aparece al hacer click
- [x] Modal muestra URL: `https://app.offsideclub.es/invite/...`
- [x] Console muestra data attributes con `inviteUrl`
- [x] Botón "Copiar" funciona
- [x] Botón "WhatsApp" abre WhatsApp con el URL

### En WhatsApp ✅
- [x] URL aparece como link azul (clickeable)
- [x] Link es clickeable en desktop y móvil
- [x] Link funciona en SMS, Email, Telegram, Discord, etc

### En Dispositivo Móvil (CON app) 🔄
- [ ] APK instalado correctamente
- [ ] App abre sin errores
- [ ] Click en URL abre app
- [ ] App muestra pantalla de invitación
- [ ] Botón "Unirme" funciona
- [ ] Usuario se une al grupo

### En Dispositivo Móvil (SIN app) 📱
- [ ] Click en URL abre navegador
- [ ] Página carga correctamente
- [ ] Botón "Unirme" funciona
- [ ] Usuario se une al grupo desde web

---

## Troubleshooting

### La modal NO muestra HTTPS URL

**Posible causa**: Caché vieja o página sin refrescar

**Solución**:
```
1. Hard refresh: Ctrl+Shift+R (o Cmd+Shift+R en Mac)
2. O abre en incógnito: Ctrl+Shift+N
3. Vuelve a probar
```

### El link NO es clickeable en WhatsApp

**Posible causa**: WhatsApp no reconoce el URL

**Solución**:
```javascript
// En console, verifica que sea HTTPS
const url = document.getElementById('inviteMessage').value;
console.log(url.includes('https://'));  // Debería ser true
```

### El link abre en navegador pero muestra error

**Posible causa**: Ruta `/invite/{code}` no funciona

**Solución**:
```bash
# Verifica que la ruta existe
php artisan route:list | grep invite
# Debería mostrar: GET /invite/{code}

# Si no aparece, ejecuta:
php artisan migrate:fresh --seed
```

---

## Resumen Rápido

**¿Qué cambió?**
- URLs ahora son HTTPS en lugar de offsideclub://
- URLs son clickeables en WhatsApp, SMS, Email, etc
- Máxima compatibilidad con todas las plataformas

**¿Cómo pruebo?**
1. Web: Abre grupo → Compartir → Ver URL HTTPS
2. WhatsApp: Envía el URL → Debería ser clickeable (azul)
3. Móvil: Click en URL → Abre invitación o app

**¿Por qué HTTPS en lugar de offsideclub://?**
- WhatsApp no reconoce esquemas personalizados
- HTTPS funciona en TODAS las apps
- Android App Links puede interceptar las HTTPS si la app está instalada
- Fallback automático a web si no está instalada

---

**¿Necesitas ayuda?** 📞

Pídeme que:
1. Debuguee URLs en consola
2. Verifique que el código se generó correctamente
3. Reinstale APK si tienes problemas
4. Revise los logs del servidor

