# Plan de publicación con Capacitor (prioridad Android)

## 1. Preparación transversal
- Verificar versiones de `Node`, `npm`, `Android Studio` y `Xcode` compatibles con la versión actual de Capacitor.
- Ejecutar `npm install` y asegurar que `capacitor.config.ts` tenga `version` y `buildNumber` alineados con el roadmap de releases.
- Actualizar `app version` y `versionCode` desde el `package.json` y propagar con `npm version <patch|minor>` para mantener trazabilidad.
- Generar los artefactos web definitivos:

```bash
npm run lint && npm run test
npm run build
npx cap sync
npm run build:mobile # Ejecuta build + sync en un solo paso
```

- Revisar assets obligatorios (íconos, splash, capturas) en las dimensiones exigidas por ambas stores. Centralizar en `resources/store-assets/`.
- El `webDir` por defecto es `mobile-shell` (fallback con mensaje in-app). Configura `CAPACITOR_SERVER_URL` para apuntar a la web hospedada antes de cualquier build de producción.
- Confirmar cumplimiento legal: política de privacidad, términos, etiquetado de datos (Play Data Safety, Apple Privacy Nutrition Labels).

## 2. Prioridad inmediata: release Android (APK/AAB)
1. **Configurar entorno**
   - Instalar/actualizar Android Studio Iguana o superior, SDK 34, NDK si se requiere código nativo.
   - Verificar `local.properties` con rutas correctas a `JAVA_HOME` y `ANDROID_HOME`.
2. **Sincronización Capacitor**

```bash
npx cap sync android
npm run cap:android
npx cap open android
```

3. **Ajustes en Android Studio**
   - Revisar `android/app/build.gradle`: `versionCode`, `versionName`, `minSdk`, `targetSdk`, `signingConfigs` (usar keystore de producción cifrada en secret manager).
   - Activar `ViewBinding`/`Jetifier` sólo si es necesario para reducir tiempos de build.
4. **Firmado y build**
   - Configurar keystore en `gradle.properties` (sin subir credenciales al repo).
   - Generar `bundleRelease` prioritariamente (`Build > Generate Signed App Bundle...`). Guardar `.aab` y opcionalmente `.apk` para QA manual.
5. **QA previo**
   - Instalar el `.apk` en dispositivos físicos (Android 13/14) y correr smoke tests: login, navegación offline, push notifications.
   - Usar `Play Console > Internal testing` para distribuir el `.aab` rápidamente.
6. **Checklist Play Console**
   - Completar `App Content > Data Safety`, clasificaciones por edades, testers internos.
   - Subir capturas obligatorias (7"/10" tablets si aplica).
   - Redactar release notes en español e inglés.
7. **Publicación**
   - Promover a `Closed testing` → `Open testing` → `Production` siguiendo métricas de estabilidad (crash rate < 1%).
   - Monitorizar Play Integrity y ANRs desde la consola.

## 3. Release iOS (posterior)
1. **Entorno**
   - Xcode 15+, CocoaPods actualizado (`sudo gem install cocoapods`).
   - Certificados: `Apple Distribution` + `App Store Connect API Key` para automatizar.
2. **Sincronización y build**

```bash
npx cap sync ios
npm run cap:ios
npx cap open ios
```

   - Ejecutar `pod install` dentro de `ios/App` si Xcode lo solicita.
3. **Configuración en Xcode**
   - Ajustar `General > Version` y `Build` (coinciden con Android). `Signing & Capabilities` con perfiles de producción, Push/Background Modes según funcionalidades.
4. **Pruebas**
   - Corrida en simuladores y dispositivos reales con `TestFlight` (Interno + Externo). Validar performance en iPhone SE y iPad.
5. **App Store Connect**
   - Crear registro de app (bundle ID ya existente), subir `.ipa` con `xcodebuild -exportArchive` o `Transporter`.
   - Completar `Privacy Nutrition Labels`, `App Review Information`, capturas en diferentes resoluciones, palabras clave y descripción localizadas.
6. **Envío a revisión**
   - Adjuntar notas para revisión (cuentas demo, flujos especiales).
   - Post-revisión, lanzar por fases (Available for Sale con `Manual release`).

## 4. Checklist post-lanzamiento
- Monitorizar analíticas (Firebase Crashlytics, App Store metrics) durante las primeras 72 h.
- Activar alertas Slack/Email cuando se detecten ANR/crash spikes.
- Documentar retroalimentación y planear la siguiente iteración en `RELEASE_NOTES.md`.

## 5. Cronograma sugerido
- **Día 1-2**: Preparación transversal, build web, verificación legal.
- **Día 3**: Android Studio ajustes + generación AAB.
- **Día 4**: QA interno Android + subida Play Console (internal testing).
- **Día 5**: Escalado a producción Android si métricas son estables.
- **Día 6-7**: Configuración iOS, TestFlight interno.
- **Día 8**: Envío App Store Review y seguimiento hasta aprobación.
