# 🧪 Bug 3: Firebase Mobile Push - Guía de Testing Completa

**Fecha:** 4 febrero 2026  
**Rama:** `feature/bug3-firebase-notifications`  
**Status:** Listo para Testing

---

## 🎯 Objetivo del Testing

Verificar que las notificaciones push funcionen correctamente en:
1. ✅ Web (debe seguir funcionando como antes)
2. 📱 App Móvil Android (nueva funcionalidad)
3. 📱 App Móvil iOS (nueva funcionalidad)

Y que los tokens se sincronicen correctamente en cada plataforma.

---

## 📋 Checklist de Pre-Testing

### Backend
- [ ] `php artisan migrate` ejecutado
- [ ] Credenciales de Firebase en `storage/app/`
- [ ] Jobs de notificaciones compilados sin errores
- [ ] Logs habilitados en `storage/logs/`

### Frontend
- [ ] `npm install` completado
- [ ] `npm run build` completado sin errores
- [ ] Firebase JS SDK disponible

### Capacitor
- [ ] `npx cap sync` ejecutado
- [ ] `google-services.json` descargado en `android/app/`
- [ ] APK compilado (o simulador listo)

---

## 🌐 Fase 1: Testing Web

### Paso 1: Limpiar Base de Datos de Prueba
```bash
# Limpiar tokens anteriores
sqlite3 database/database.sqlite << EOF
DELETE FROM push_subscriptions;
EOF
```

### Paso 2: Verificar Infraestructura
```bash
# Verificar que Firebase está habilitado
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### Paso 3: Abrir Web en Browser
```bash
# Navegar a http://localhost:8000
# Abrir DevTools > Console
```

### Paso 4: Verificar Logs de Firebase
En la consola del browser, deberías ver:
```javascript
[FirebaseNotificationService] Inicializado en plataforma: web
[FirebaseNotificationService] Inicializando para Web...
[FirebaseNotificationService] Permisos de notificación concedidos en web
[FirebaseNotificationService] Token de web obtenido y registrado
```

### Paso 5: Verificar Token en BD
```bash
# Ver tokens registrados
sqlite3 database/database.sqlite << EOF
SELECT id, user_id, platform, device_token FROM push_subscriptions LIMIT 5;
EOF
```

**Debe mostrar:**
```
id | user_id | platform | device_token
1  | 1       | web      | abc123...
```

### Paso 6: Enviar Notificación de Prueba
```bash
# Crear grupo de prueba si no existe
php artisan tinker
>>> $group = App\Models\Group::first();
>>> dispatch(new App\Jobs\SendNewPredictiveQuestionsPushNotification($group->id, 1));
```

### Paso 7: Verificar Notificación en Browser
- ✅ Debe aparece notificación toast en la esquina
- ✅ Debe contener título y descripción correctos
- ✅ Clickeable y navega al link

### Paso 8: Verificar Logs
```bash
# Revisar logs de aplicación
tail -100 storage/logs/laravel.log | grep "Notificación"
```

Debe mostrar:
```
[INFO] Notificación enviada a usuario
  user_id: 1
  user_name: John
  platform: web
```

---

## 📱 Fase 2: Testing Android (Simulador)

### Paso 1: Preparar Google Services JSON
```bash
# Descargar de Firebase Console
# Copiar a android/app/google-services.json
```

### Paso 2: Build APK en Android Studio
```bash
# Abrir en Android Studio
npx cap open android

# En Android Studio:
# Build > Build Bundle(s) / APK(s) > Build APK(s)
# Esperar a que compile
# Ver en: android/app/build/outputs/apk/
```

### Paso 3: Instalar en Simulador
```bash
# En terminal (con simulador corriendo)
adb install -r android/app/build/outputs/apk/debug/app-debug.apk

# O directamente desde Android Studio:
# Run > Run 'app'
```

### Paso 4: Abrir App
- Iniciar app en el simulador
- Debe pedir permisos de notificación
- Aceptar permisos

### Paso 5: Verificar Token en Logs de App
En Android Studio, Logcat debe mostrar:
```
[FirebaseNotificationService] Inicializado en plataforma: android
[FirebaseNotificationService] Inicializando para Capacitor...
[FirebaseNotificationService] Permisos de notificación concedidos en Capacitor
[FirebaseNotificationService] Token de android obtenido y registrado
```

### Paso 6: Verificar Token en BD
```bash
# En laptop, ver tokens
sqlite3 database/database.sqlite << EOF
SELECT user_id, platform, device_token FROM push_subscriptions WHERE platform = 'android';
EOF
```

### Paso 7: Enviar Notificación
```bash
php artisan tinker
>>> $group = App\Models\Group::first();
>>> dispatch(new App\Jobs\SendNewPredictiveQuestionsPushNotification($group->id, 1));
```

### Paso 8: Verificar Notificación en App

#### Foreground (App abierta)
- [ ] Notificación aparece en top bar
- [ ] Contiene título y descripción
- [ ] Se puede hacer click
- [ ] Al hacer click navega al link

#### Background (App cerrada)
- [ ] Notificación aparece en bandeja de notificaciones
- [ ] Se puede expandir para ver detalles
- [ ] Hacer swipe down desde top
- [ ] Al hacer click abre app y navega

### Paso 9: Verificar Logs
```bash
tail -100 storage/logs/laravel.log | grep "android"
```

---

## 📱 Fase 3: Testing iOS (Similar a Android)

**Nota:** Se recomienda testing en dispositivo real para iOS (los simuladores tienen limitaciones).

### Pasos Similares a Android
1. Compilar en Xcode
2. Instalar en simulador o dispositivo
3. Aceptar permisos
4. Verificar logs
5. Enviar notificación de prueba
6. Verificar en foreground y background

---

## 🔍 Debugging

### Si notificaciones no llegan a web:
```javascript
// En consola del browser
firebase.messaging().getToken().then(token => {
    console.log('Token actual:', token);
}).catch(err => {
    console.error('Error obteniendo token:', err);
});
```

### Si notificaciones no llegan a Android:
```bash
# En Logcat de Android Studio
adb logcat | grep "Firebase\|FCM\|Notification"

# Verificar permisos
adb shell pm list permissions | grep -i notify
```

### Si tokens no se guardan en BD:
```bash
# Verificar que la request llegó al backend
tail -100 storage/logs/laravel.log | grep "/api/push/token"

# Debe mostrar:
# [INFO] Registrando token push
#   user_id: 1
#   platform: android|ios
#   token: abc123...
```

### Ver errores de Firebase
```bash
# En artisan tinker
>>> Log::get();  // Ver logs recientes
>>> Log::getLevelName(); // Nivel de logging
```

---

## 📊 Matriz de Testing

| Escenario | Web | Android | iOS | Status |
|-----------|-----|---------|-----|--------|
| Token se registra | ⬜ | ⬜ | ⬜ | Pendiente |
| Token en BD con platform correcto | ⬜ | ⬜ | ⬜ | Pendiente |
| Notificación en foreground | ⬜ | ⬜ | ⬜ | Pendiente |
| Notificación en background | ⬜ | ⬜ | ⬜ | Pendiente |
| Al hacer click navega | ⬜ | ⬜ | ⬜ | Pendiente |
| Título y body son correctos | ⬜ | ⬜ | ⬜ | Pendiente |
| Icon se muestra | ⬜ | ⬜ | ⬜ | Pendiente |
| Badge actualiza | ⬜ | ⬜ | ⬜ | Pendiente |
| Sound se reproduce | ⬜ | ⬜ | ⬜ | Pendiente |

**Leyenda:** ⬜ = No testado | 🟡 = En progreso | ✅ = Pasado | ❌ = Falló

---

## 🚨 Casos de Error

### Error: "Firebase credentials not found"
**Solución:** Verificar que `storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json` existe

### Error: "Invalid device token"
**Solución:** Token expirado. El frontend debe renovarlo automáticamente

### Error: "User unauthorized"
**Solución:** Token de Sanctum expirado. Fazer logout/login

### Error: "APK no compila"
**Solución:** 
- Limpiar: `cd android && ./gradlew clean`
- Verificar google-services.json en `android/app/`

---

## ✅ Criterios de Aceptación

### Para considerar Bug 3 como RESUELTO:
1. ✅ Notificaciones llegan a web (como antes)
2. ✅ Notificaciones llegan a Android en foreground
3. ✅ Notificaciones llegan a Android en background
4. ✅ Notificaciones llegan a iOS en foreground
5. ✅ Notificaciones llegan a iOS en background
6. ✅ Tokens correctos en BD para cada plataforma
7. ✅ Click en notificación navega correctamente
8. ✅ Renovación de tokens funciona

---

## 📝 Reporte de Testing

### Completar después de testing:
```markdown
## Resultado del Testing - [Fecha]

### Web
- Token registrado: [SI/NO]
- Notificación en foreground: [SI/NO]
- Click funciona: [SI/NO]

### Android
- Token registrado: [SI/NO]
- Notificación en foreground: [SI/NO]
- Notificación en background: [SI/NO]
- Click funciona: [SI/NO]

### iOS
- Token registrado: [SI/NO]
- Notificación en foreground: [SI/NO]
- Notificación en background: [SI/NO]
- Click funciona: [SI/NO]

### Problemas encontrados:
1. [Problema 1]
2. [Problema 2]

### Soluciones aplicadas:
1. [Solución 1]
2. [Solución 2]
```

---

## 🎯 Próximos Pasos After Testing

1. Si hay bugs: Crear nuevas ramas para fixes
2. Merge a `main` cuando todos los tests pasen
3. Deploy a producción
4. Monitoreo de logs en producción
5. Comunicar a usuarios que notificaciones ya funcionan en mobile

