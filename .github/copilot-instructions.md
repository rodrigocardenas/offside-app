# Instrucciones para Offside Club: Laravel & Capacitor

Eres experto en **Laravel 11, PHP 8.3+, Capacitor 6+, TypeScript** y **Google Gemini AI**. Estás trabajando en **Offside Club**, una plataforma de predicciones deportivas con integración de IA y sistema de grupos competitivos.

---

## 📱 Contexto de Offside Club

**Offside Club** es una app mobile (Android/iOS) buildida con **Capacitor** que permite a usuarios:

- **Hacer predicciones** sobre partidos reales (datos de Football-Data.org)
- **Competir en ranking** individual y por grupos
- **Chat en tiempo real** en grupos de competición
- **Análisis con IA** (Google Gemini) con grounding de datos actualizados
- **Notificaciones push** via Firebase

### Stack Tecnológico

#### Backend
- **Laravel 10+** (API REST)
- **PHP 8.1+** (moderna: constructor promotion, readonly properties)
- **Google Gemini AI** (hosseinhezami/laravel-gemini)  
- **Firebase PHP** (push notifications)
- **Intervention Image** (manipulación de imágenes via CloudFlare Images)
- **Laravel Horizon** (queue management)
- **Vite** (asset bundling)
- **Web Push** (notificaciones web)

#### Frontend Mobile (Capacitor)
- **Capacitor 6+** (compila a Android nativo)
- **TypeScript** (type safety)
- **Alpine.js** (interactividad)
- **Tailwind CSS** (estilos)
- **Firebase Messaging** (push notifications)
- **Canvas API** (gráficos)

---


### Comandos Principales

```bash
# Desarrollo
npm run build-views            # Compila assets (JS/CSS) y views

# Build mobile
npm run build:mobile           # npm run build + npx cap sync
npm run cap:android            # Abre Android Studio
npm run cap:ios                # Abre Xcode (iOS)

# Después de cambios en frontend
npx cap sync                   # Sincroniza cambios a plataformas

# Debugging
npx cap open android           # Abre proyecto en Android Studio
```


### Prefiere Pest


## 📋 Equipo de Desarrollo

### Comandos Comunes

```bash
# Setup inicial
composer install
npm install
php artisan migrate --seed

# Desarrollo
npm run dev          # Terminal 1: Vite watch
php artisan serve    # Terminal 2: API server
npx cap sync         # Terminal 3: Sync a Capacitor

# Build production
npm run prod         # Cache + optimize
npx cap sync android # Sync a Android

# Deploy
bash scripts/deploy.sh  # Script de deploy (chequea rama, etc.)


## 📚 Documentación Relevante

- **[docs/START_HERE.md](../docs/START_HERE.md)** — Punto de partida
- **[docs/SECURITY_CHECKLIST.md](../docs/security/SECURITY_CHECKLIST.md)** — Seguridad en producción
- **[docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md](../docs/deployments/PRODUCTION_DEPLOYMENT_CHECKLIST.md)** — Deploy

---

## 🚀 Quick Start

1. **Clona + setup:**
   ```bash
   git clone <repo>
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

2. **Desarrollo:**
   ```bash
   server en laragon  # (http://offsideclub.test)
   ```

3. **Capacitor sync:**
   ```bash
   npx cap sync android
   npx cap open android  # Android Studio
   ```

4. **Tests:**
   ```bash
   php artisan test
   ```

---

## 🛠️ Reglas de Terminal y Comandos

- Usa exclusivamente sintaxis de **Bash** para todos los comandos de terminal. No uses PowerShell ni CMD.
- **Prohibición estricta:** No utilices nunca el comando `tail` (ni solo ni al final de un pipeline), ya que bloquea el flujo de ejecución.
- Si necesitas leer el final de un archivo, utiliza alternativas como `sed`, `awk`, o simplemente `cat` si el archivo es pequeño.

---

## 💡 Reglas Generales

- **Siempre commit messages claros:** `feat: add prediction scoring` o `fix: handle null match edge case`
- **Code review antes de merge:** PR con tests pasando
- **Sigue Laravel & PHP standards:** Pint formatter
- **Documenta APIs:** Comments en métodos públicos
- No uses `dd()` en controladores; usa `Log::info()` para que el MCP de Laravel pueda leer los logs.

## Herramientas MCP
- Para tareas complejas, usa **Laravel Boost** para validar rutas y modelos antes de sugerir cambios en el Backend.
- Usa **Chrome DevTools** para depurar la interfaz de usuario en el emulador de Android.
- Usa **Playwright** para verificar que los cambios en la API no rompan el frontend de Capacitor.
---



### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

