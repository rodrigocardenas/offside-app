---
description: Compilar las vistas web y preparar el paquete nativo para Android
---

Prepara una versión buildida de la aplicación móvil y sincroniza hacia Capacitor.

// turbo
1. Construir las vistas y compilar recursos:
`npm run build-views`

// turbo
2. Sincronizar hacia las carpetas nativas de Android/iOS:
`npx cap sync`

3. Abrir la capa de Android Studio para empaquetado manual:
`npx cap open android`
