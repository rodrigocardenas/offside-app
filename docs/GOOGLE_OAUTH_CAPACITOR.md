# 🔐 Google OAuth - Solución para Capacitor/Android

## ⚠️ Problema Identificado

En Android/Capacitor:
1. Al hacer click en "Google Login", abre Chrome
2. Google te autentica en Chrome
3. Intenta redirigir a `https://app.offsideclub.es/auth/google/callback`
4. Pero Capacitor no puede interceptar el callback de Chrome → vuelve al login

## ✅ Solución: Endpoints API para Móvil

Se crearon dos nuevos endpoints sin CSRF validation:

```
POST /api/auth/mobile/google-login
GET  /api/auth/mobile/google-url
```

### Flujo en Capacitor:

```
1. User hace click en "Google"
   ↓
2. Capacitor abre InAppBrowser a: /api/auth/mobile/google-url
   ↓
3. Google autentica al usuario EN InAppBrowser
   ↓
4. Callback vuelve a InAppBrowser (mismo app)
   ↓
5. Capacitor intercepta el URL y extrae token
   ↓
6. Envía POST a /api/auth/mobile/google-login con token
   ↓
7. Backend autentica y devuelve user data
   ↓
8. App guarda token en localStorage/storage
   ↓
9. Navega a /groups
```

---

## 🛠️ Implementación en Frontend (TypeScript - Capacitor)

### Instalación de dependencias:

```bash
npm install @capacitor-community/http
npm install @capacitor/browser
```

### Código TypeScript para login con Google:

```typescript
// src/pages/Login.tsx o similar
import { InAppBrowser } from '@capacitor-community/inappbrowser';
import { Http } from '@capacitor-community/http';
import { useNavigation } from '@react-navigation/native';

const handleGoogleLogin = async () => {
  try {
    // 1. Obtener URL de Google OAuth desde el servidor
    const response = await Http.get({
      url: 'https://app.offsideclub.es/api/auth/mobile/google-url',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.data.success) {
      throw new Error('Failed to get Google auth URL');
    }

    // 2. Abrir en InAppBrowser
    const result = await InAppBrowser.open({
      url: response.data.url,
      windowName: '_self',
    });

    if (result.type === 'cancel') {
      console.log('Google login cancelled');
      return;
    }

    // 3. El callback debería volver automaticamente
    // Si el usuario se autentica, el servidor maneja todo
    // y devuelve un redirect a la app
    
  } catch (error) {
    console.error('Google login error:', error);
    Alert.alert('Error', 'Failed to login with Google');
  }
};
```

### Alternativa: Si necesitas manejo manual del callback:

```typescript
// En el callback URL, Capacitor puede interceptar con deep links
// Agregar en capacitor.config.ts:
// ios URL: offsideclub://callback
// android URL: offsideclub://callback

// Luego escuchar en App.tsx:
import { App } from '@capacitor/app';
import { useEffect } from 'react';

useEffect(() => {
  App.addListener('appUrlOpen', data => {
    const slug = data.url.split('.com').pop();
    if (slug) {
      // Procesar el deep link
      handleDeepLink(slug);
    }
  });
}, []);

const handleDeepLink = async (slug: string) => {
  // Por ejemplo: /auth/google/callback?code=XXX&state=XXX
  if (slug.includes('/auth/google/callback')) {
    const code = new URLSearchParams(slug).get('code');
    const state = new URLSearchParams(slug).get('state');
    
    // Enviar el code al endpoint mobile
    const result = await Http.post({
      url: 'https://app.offsideclub.es/api/auth/mobile/google-login',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      data: {
        code: code,
        state: state,
      },
    });
    
    if (result.data.success) {
      // Login exitoso, guardar token y navegar
      await SecureStorage.setPassword('user_token', result.data.token);
      navigation.replace('Home');
    }
  }
};
```

---

## 🚀 Endpoints Backend

### POST `/api/auth/mobile/google-login`

**Parámetros:**
```json
{
  "id_token": "token_de_google_opcional",
  "timezone": "America/Madrid"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Logged in successfully",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@gmail.com",
    "avatar": "https://..."
  },
  "redirect": "/"
}
```

---

## 🔄 Deep Links (Alternativa)

Si prefieres usar deep links en lugar de InAppBrowser:

### 1. Configurar Capacitor (capacitor.config.ts):

```typescript
{
  appId: 'es.offsideclub.app',
  appName: 'Offside Club',
  plugins: {
    AndroidDeepLink: {
      redirect: 'https://app.offsideclub.es/auth/google/callback',
    },
  },
}
```

### 2. Agregar Deep Link en Google Cloud Console:

En "OAuth consent screen", agregar:
```
offsideclub://auth/google/callback
```

### 3. AndroidManifest.xml:

```xml
<intent-filter>
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data
    android:scheme="offsideclub"
    android:host="auth" />
</intent-filter>
```

---

## 🧪 Testing Local

```bash
# 1. Build APK
npm run build:mobile
npx cap open android

# 2. En Android Studio: Run app
# 3. Ir a login y hacer click en "Google"
# 4. Verificar en device logs:
adb logcat | grep offsideclub
```

---

## ⚠️ Próximos Pasos

1. **Implementar MobileOAuthController** (ya creado)
2. **Crear front-end Capacitor** con InAppBrowser
3. **Configurar Deep Links** en AndroidManifest.xml
4. **Agregar deep links URL** a Google Cloud Console
5. **Testing** en emulador y device real

---

## 📞 Troubleshooting

**Error: "Chrome redirects but app doesn't load"**
- Solución: Usar InAppBrowser en lugar de navegación por defecto

**Error: "403 org_internal"**
- Solución: OAuth app está en "Internal" mode, cambiar a "External" en Google Console

**Error: "State mismatch"**
- Solución: Usar `stateless()` en Socialite (ya implementado)

