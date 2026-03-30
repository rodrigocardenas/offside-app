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

## 🚀 Deployment a Producción

### Configuración Inicial (Una sola vez)

**Nota sobre `.env.deploy`:**
El script de deploy puede leer la configuración desde los siguientes archivos en orden de prioridad:
1. Variables de entorno actuales (`$SSH_KEY_PATH`)
2. Archivo local `~/.offside-deploy.env` (en tu home)
3. Archivo en el proyecto (no committeado): `.env.deploy`

1. **Obtener la clave SSH:**
   - Solicita el archivo `offside-new.pem` al DevOps/Administrador
   - Guárdalo en tu máquina local (ej: `~/.ssh/offside-new.pem`)

2. **Configurar variables de entorno:**
   ```bash
   # Opción 1: Variable de entorno temporal (sesión actual)
   export SSH_KEY_PATH=~/.ssh/offside-new.pem
   
   # Opción 2: Archivo de configuración permanente (~/.offside-deploy.env)
   echo 'SSH_KEY_PATH=~/.ssh/offside-new.pem' >> ~/.offside-deploy.env
   source ~/.offside-deploy.env
   
   # Opción 3: Archivo local en el proyecto (NO COMMITTEADO - agregar a .gitignore)
   # Crear .env.deploy en la raíz del proyecto:
   # SSH_KEY_PATH=/ruta/a/offside-new.pem
   # DEPLOY_SERVER=ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
   ```

3. **Verificar permisos (Linux/macOS):**
   ```bash
   chmod 600 ~/.ssh/offside-new.pem
   ```

### Proceso de Deploy

**Requisitos antes de ejecutar:**
- ✅ Estar en rama `main`
- ✅ Todos los cambios deben estar committed
- ✅ Pasar todos los tests del grupo `@group deploy` (CriticalViewsTest)
- ✅ No tener cambios locales sin guardar

**Ejecutar deploy:**
```bash
# Con SSH configurado
bash scripts/deploy.sh
```

**El script hará automáticamente:**
1. ✅ Validar que estás en rama `main`
2. ✅ Ejecutar `php artisan test --group=deploy` (CriticalViewsTest)
3. ✅ Compilar assets con `npm run build`
4. ✅ Crear archivo `build.tar.gz` con los cambios
5. ✅ Transferir via SSH a servidor de producción
6. ✅ Ejecutar migraciones en producción
7. ✅ Hacer git pull de main
8. ✅ Recargar servicios (queue, PHP-FPM, nginx)

### Troubleshooting de Deploy

**Error: "No se encontró SSH_KEY_PATH"**
```bash
export SSH_KEY_PATH=~/.ssh/offside-new.pem
bash scripts/deploy.sh
```

**Error: "Estás en la rama X, se permite desde main"**
- Cambiar a rama main: `git checkout main`
- Asegurar que main está actualizado: `git pull origin main`

**Error: "Tienes cambios locales sin guardar"**
- Hacer commit: `git add . && git commit -m "tu mensaje"`
- O descartar cambios: `git restore .`

**Error: "Algunos tests críticos fallaron"**
- Revisar qué tests fallaron
- Corregir el código o los tests
- Hacer commit de cambios: `git commit ...`
- Intentar deploy nuevamente

### Tests Críticos (Grupo @group deploy)

El archivo `tests/Feature/CriticalViewsTest.php` contiene 24 tests que validan:
- **17 tests** - Carga correcta de vistas (groups, profile, login, rankings)
- **7 tests** - Envío y validación de respuestas a preguntas

Todos deben pasar antes de deployar a producción.

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

