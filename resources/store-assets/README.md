# Assets para publicación en stores

Mantén este directorio sincronizado con los artefactos que requieren Google Play y App Store. Sugiere usar subcarpetas `android/`, `ios/` y `shared/` para mantener ordenados los archivos.

## Checklist rápido
- **Iconos**
  - 1024x1024 (PNG sin transparencia para Play Store, PNG con transparencia para App Store).
  - Versiones cuadradas 512x512, 192x192 para referencias internas.
- **Splash / Launch Screen**
  - 2732x2732 (vector o PNG), con zona segura de 1200x1200 centrada.
- **Capturas de pantalla**
  - Android: 6.7" (1080x2400) y tablet 10" (1920x1200) mínimo 2 por categoría.
  - iOS: 6.5" (1242x2688), 5.5" (1242x2208) y iPad Pro 12.9" (2048x2732).
- **Video promocional (opcional)**
  - Android: enlace a YouTube no listado.
  - iOS: App Preview 15-30s en 1080x1920.
- **Legal**
  - PDF de política de privacidad
  - Texto para etiquetas de privacidad (Data Safety, Nutrition Labels)

## Buenas prácticas
1. Nombrar archivos con el patrón `<plataforma>-<categoria>-<resolution>.png`.
2. Guardar el origen editable (Figma/PSD) en la carpeta `resources/store-assets/source/` (añadir a .gitignore si son pesados).
3. Documentar en este README cualquier cambio significativo para que marketing y desarrollo tengan un único punto de verdad.
