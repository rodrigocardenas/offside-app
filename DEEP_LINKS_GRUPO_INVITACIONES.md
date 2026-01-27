# Deep Links Implementation - Grupos con Invitaciones

## ¿Cuál es el Problema?

**Antes**:
- URL de invitación: `https://app.offsideclub.es/groups/invite/{code}`
- Al enviar por WhatsApp, era un link web normal
- Si el usuario tenía la app, se abría el navegador (mala UX)
- Si lo compartían a otro dispositivo, se abría el navegador

**Ahora**:
- URL de invitación: `offsideclub://invite/{code}`
- Al hacer click desde cualquier dispositivo, intenta abrir la app
- Si no tienen la app, pueden usar link web de fallback
- Mejor experiencia: si tienes app, te va directo; si no, opción web

---

## ¿Cómo Funciona Ahora?

### 1. Usuario hace Click en "Compartir" en Grupo

```
Grupo "Champions League 2026"
    ↓
Click botón "Compartir"
    ↓
Modal con 2 opciones: Copiar o Whatsapp
```

### 2. Modal Genera Deep Links

**Lo que ve el usuario**:
```
¡Únete al grupo "Champions League 2026" en Offside Club!

offsideclub://invite/abc123xyz

¡Ven a competir con nosotros!
```

**Data en background**:
```javascript
deepLink = "offsideclub://invite/abc123xyz"
webUrl = "https://app.offsideclub.es/groups/invite/abc123xyz"
code = "abc123xyz"
```

### 3. Compartir por WhatsApp

**Mensaje que se envía**:
```
¡Únete al grupo en Offside Club!

offsideclub://invite/abc123xyz

Si tienes la app instalada, este link te llevará directamente. 
Si no, puedes usar: https://app.offsideclub.es/groups/invite/abc123xyz

¡Ven a competir con nosotros!
```

### 4. Receptor Recibe el Link

**Si tiene la app móvil**:
- Click en `offsideclub://invite/abc123xyz`
- Android intercepta (intent-filter)
- MainActivity abre con el deep link
- @capacitor/app dispara evento `appUrlOpen`
- DeepLinksHandler parsea: `invite/abc123xyz`
- Navega a: `/invite/{code}`
- Muestra modal de invitación/confirmación
- Usuario acepta → Se une al grupo ✅

**Si NO tiene la app**:
- Click intenta abrir `offsideclub://` → No encuentra app
- Android fallback → Muestra el link web `https://app.offsideclub.es/groups/invite/abc123xyz`
- Usuario puede aceptar abrirlo en navegador
- Se une al grupo desde web ✅

---

## Cambios de Código

### 1. `group-card.blade.php` (sin cambios necesarios)
- Ya tiene el botón "Compartir" que llama `showInviteModal()`

### 2. `index.blade.php` (Actualizado)

**Función `showInviteModal()`**:
```javascript
// Extrae el código de la URL
const code = inviteUrl.split('/').pop();

// Genera URLs
const webUrl = window.location.origin + '/groups/invite/' + code;
const deepLink = 'offsideclub://invite/' + code;

// Muestra deep link en modal
const message = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${deepLink}\n\n...`;

// Guarda datos para compartir
messageArea.dataset.deepLink = deepLink;
messageArea.dataset.webUrl = webUrl;
messageArea.dataset.code = code;
```

**Función `shareOnWhatsApp()`**:
```javascript
// Recupera URLs guardadas
const deepLink = messageArea.dataset.deepLink;
const webUrl = messageArea.dataset.webUrl;

// Mensaje con ambas opciones
const text = `¡Únete al grupo en Offside Club!\n\n${deepLink}\n\nSi tienes la app instalada, este link te llevará directamente. Si no, puedes usar: ${webUrl}\n\n¡Ven a competir con nosotros!`;

// Envía a WhatsApp
window.open(`https://wa.me/?text=${encodedMessage}`, '_blank');
```

### 3. `show.blade.php` (Actualizado igual que index.blade.php)

### 4. `resources/js/deep-links.js` (Ya soporta `invite`)

```javascript
if (host === 'invite') {
    const inviteToken = pathname.replace(/\//g, '');
    if (inviteToken) {
        this.navigateTo(`/invite/${inviteToken}`);
        return;
    }
}
```

### 5. `AndroidManifest.xml` (Ya configurado)

```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="offsideclub" />
</intent-filter>
```

---

## Flujo Completo de Invitación

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuario en Grupo "Champions"                                 │
│    Click botón "Compartir"                                      │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Modal de Invitación abre                                     │
│    Muestra: offsideclub://invite/abc123xyz                      │
│    Botones: Copiar | WhatsApp                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
        ▼                 ▼
    ┌────────────┐  ┌──────────────┐
    │   COPIAR   │  │   WHATSAPP   │
    └─────┬──────┘  └────────┬─────┘
          │                  │
          ▼                  ▼
    ┌──────────────┐  ┌─────────────────────────────┐
    │ Portapapeles │  │ Se abre WhatsApp con mensaje │
    │              │  │ offsideclub://invite/abc123  │
    │ Usuario copia│  │ y link web de fallback       │
    │ pega donde   │  │                              │
    │ quiere       │  │ Usuario envía                │
    └──────┬───────┘  └────────────┬────────────────┘
           │                       │
           ▼                       ▼
        ┌─────────────┐  ┌──────────────────────────────┐
        │ Lo comparte │  │ Receptor abre WhatsApp       │
        │ (SMS, chat, │  │ y ve el link deep + web      │
        │ email, etc) │  │                              │
        └──────┬──────┘  └────────────┬─────────────────┘
               │                      │
               ▼                      ▼
        ┌─────────────┐  ┌──────────────────────────────┐
        │ Receptor    │  │ Receptor hace click en link  │
        │ lo recibe y │  │ offsideclub://invite/abc123  │
        │ abre el     │  │                              │
        │ link        │  │                              │
        └──────┬──────┘  └────────────┬─────────────────┘
               │                      │
               ▼                      ▼
        ┌─────────────┐  ┌──────────────────────────────┐
        │ Si tiene    │  │ Android detecta esquema      │
        │ app: abre   │  │ offsideclub://               │
        │ directamente│  │ intercepta intent-filter     │
        │             │  │ abre MainActivity            │
        │ Si no: copia│  │                              │
        │ link web    │  └────────────┬─────────────────┘
        │ para pegar  │               │
        │ en navegador│               ▼
        └─────────────┘  ┌──────────────────────────────┐
                         │ @capacitor/app dispara       │
                         │ appUrlOpen event             │
                         │ con URL: offsideclub://...   │
                         └────────────┬─────────────────┘
                                      │
                                      ▼
                         ┌──────────────────────────────┐
                         │ DeepLinksHandler procesa     │
                         │ Parsea: invite/abc123xyz     │
                         │ Navega a: /invite/{code}     │
                         └────────────┬─────────────────┘
                                      │
                                      ▼
                         ┌──────────────────────────────┐
                         │ Pantalla de Invitación       │
                         │ - Nombre del grupo           │
                         │ - Botón "Unirme"             │
                         │ - Botón "Cancelar"           │
                         └────────────┬─────────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                         ▼                         ▼
                    ┌──────────┐            ┌───────────┐
                    │ Cancelar │            │ Unirme    │
                    └──────────┘            │           │
                                            ├─ POST /groups/...
                                            │   /invite/{code}
                                            │
                                            ├─ Backend verifica
                                            │
                                            ├─ Usuario se une
                                            │   al grupo
                                            │
                                            ├─ Redirige a grupo
                                            │
                                            ├─ ✅ Usuario es
                                            │   miembro
                                            └───────────┘
```

---

## Testing

### Test Case 1: Compartir en WhatsApp (con app instalada)
```
1. Abre app OffsideClub
2. Ve a Grupo
3. Click "Compartir"
4. Click "WhatsApp"
5. Se abre WhatsApp con mensaje
6. Copiar link: offsideclub://invite/abc123xyz
7. Enviar a otro dispositivo/amigo
8. En dispositivo con app: Click link
9. ✅ App abre en pantalla de invitación
10. Click "Unirme"
11. ✅ Usuario se une al grupo
```

### Test Case 2: Compartir en WhatsApp (sin app instalada)
```
1. Enviar link: offsideclub://invite/abc123xyz
2. En dispositivo sin app: Click link
3. ✅ Android fallback a navegador
4. Copiar/usar link web: https://app.offsideclub.es/groups/invite/abc123xyz
5. ✅ Abre en navegador
6. Click "Unirme"
7. ✅ Usuario se une al grupo
```

### Test Case 3: Copiar Mensaje
```
1. Abre app OffsideClub
2. Ve a Grupo
3. Click "Compartir"
4. Click "Copiar"
5. ✅ Mensaje copiado con deep link
6. Pega en: WhatsApp, SMS, Email, Telegram, etc
7. Receptor lo recibe
8. Si tiene app: click link abre app
9. Si no tiene: copia link web y abre en navegador
```

---

## Logs Esperados en Dispositivo

```
[DeepLinks] Handler inicializado correctamente
[DeepLinks] Deep link detectado: offsideclub://invite/abc123xyz
[DeepLinks] URL parseada: host = invite, path = abc123xyz
[DeepLinks] Navegando a /invite/abc123xyz
```

---

## Consideraciones

### ¿Por qué mostrar ambos links en WhatsApp?
- **Deep link** (`offsideclub://`) funciona si tienes la app
- **Web link** es fallback si no tienes la app
- Usuario elige cuál usar según su dispositivo

### ¿WhatsApp reconoce el esquema `offsideclub://`?
- **En dispositivo con app**: Sí, abre la app
- **En dispositivo sin app**: No lo reconoce, pero el web link está ahí
- **La idea**: Máxima compatibilidad, máxima UX

### ¿Qué pasa si el user acepta la invitación?
- Ruta `/invite/{code}` en Laravel:
  - Verifica que el grupo existe
  - Verifica que el usuario no es miembro
  - Agrega usuario al grupo
  - Redirige a la página del grupo
  - ✅ Usuario es miembro

---

## Archivos Modificados

- ✅ `resources/views/groups/index.blade.php`
  - `showInviteModal()` - Genera deep links
  - `shareOnWhatsApp()` - Envía con ambos links

- ✅ `resources/views/groups/show.blade.php`
  - `showInviteModal()` - Genera deep links
  - `shareOnWhatsApp()` - Envía con ambos links

- ✅ `resources/js/deep-links.js` (ya soporta `invite`)
  - Procesa URLs `offsideclub://invite/{code}`
  - Navega a `/invite/{code}`

- ✅ `android/app/src/main/AndroidManifest.xml` (ya configurado)
  - Intent filter para `offsideclub://`

---

## Conclusión

**Implementación completa de deep links para invitaciones de grupos**. Ahora cuando un usuario comparte un grupo por WhatsApp/redes sociales, el link intenta abrir la app (si está instalada) o presenta fallback a web (si no está instalada).

**Estado**: ✅ **LISTO PARA COMPILAR Y TESTEAR**
