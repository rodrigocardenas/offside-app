# ⚽ Plan: Flujo de Predicción Social — Copa del Mundo 2026

> **Objetivo:** Un enlace por partido en stories de RRSS que lleva a una landing pública y temática. El usuario ve el partido, hace clic en "Predecir", ingresa solo su username, vota y puede compartir su predicción como imagen.

---

## 📐 Flujo Completo

```
Story RRSS
    ↓
GET /wc/{matchId}              ← Landing pública (sin auth)
    │  · Fondo con imagen del Mundial
    │  · Partido: México vs Sudáfrica — 11 Jun 19:00
    │  · Botón: ⚽ "Predice el resultado"
    ↓
[click botón]
    │
    ├── Si ya está autenticado → salta directo a las opciones (inline en la misma página)
    │
    └── Si es guest → Overlay / inline section:
            "¿Cómo te llaman?"
            [ input username ]  [ Entrar y predecir ]
            (usa LoginController@login existente)
            ↓
         POST /wc/{matchId}/auth     ← login rápido, redirect de vuelta
            ↓
GET /wc/{matchId}  (autenticado, con opciones de voto visibles)
    │  · 3 botones: Victoria México | Empate | Victoria Sudáfrica
    ↓
POST /wc/{matchId}/votar         ← registra el Answer
    ↓
GET /wc/{matchId}/resultado      ← pantalla de compartir (session flash)
    │  · Card visual: "Voté por Victoria México ⚽"
    │  · Botón: "Seguir prediciendo" → /wc/hoy
    │  · Botón: "Compartir" → Web Share API / copiar imagen Canvas
```

---

## 🗂️ Archivos a Crear / Modificar

### Nuevos

| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/WorldCupSocialController.php` | Controlador principal del flujo |
| `resources/views/mundial/landing.blade.php` | Landing pública del partido |
| `resources/views/mundial/resultado.blade.php` | Pantalla post-voto (share card) |
| `resources/views/mundial/hoy.blade.php` | Lista de partidos del día (opcional) |

### Modificados

| Archivo | Cambio |
|---------|--------|
| `routes/web.php` | Nuevas rutas `/wc/...` sin middleware auth |
| `app/Http/Controllers/Auth/LoginController.php` | Añadir soporte para `intended` URL en redirect post-login |

---

## 🛣️ Rutas

```php
// routes/web.php — sin middleware auth (públicas)
Route::prefix('wc')->name('wc.')->group(function () {

    // Landing de un partido específico
    Route::get('/{match}', [WorldCupSocialController::class, 'landing'])
        ->name('match');

    // Login rápido desde la landing (solo username)
    Route::post('/{match}/auth', [WorldCupSocialController::class, 'quickAuth'])
        ->name('auth')
        ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);   // no necesario: incluimos token en el form

    // Registrar voto (requiere auth, redirige a login si no)
    Route::post('/{match}/votar', [WorldCupSocialController::class, 'votar'])
        ->middleware('auth')
        ->name('votar');

    // Pantalla de resultado / compartir
    Route::get('/{match}/resultado', [WorldCupSocialController::class, 'resultado'])
        ->middleware('auth')
        ->name('resultado');

    // Todos los partidos de hoy (opcional, para el CTA "seguir prediciendo")
    Route::get('/', [WorldCupSocialController::class, 'hoy'])
        ->name('hoy');
});
```

---

## 🎮 Controlador `WorldCupSocialController`

```php
class WorldCupSocialController extends Controller
{
    // GET /wc/{match}
    // - Carga FootballMatch + Question del grupo WC
    // - Si está autenticado → $userAnswer = Answer del usuario (si ya votó)
    // - Vista: landing.blade.php
    public function landing(FootballMatch $match) { ... }

    // POST /wc/{match}/auth
    // - Igual que LoginController@login (solo username)
    // - Guarda session()->put('url.intended', "/wc/{match->id}")
    // - Redirige de vuelta a la landing
    public function quickAuth(Request $request, FootballMatch $match) { ... }

    // POST /wc/{match}/votar   [auth]
    // - Encuentra la Question del grupo WC para este match
    // - Crea/actualiza Answer via QuestionController@answer lógica
    // - Flash: option elegida, puntos
    // - Redirect → /wc/{match}/resultado
    public function votar(Request $request, FootballMatch $match) { ... }

    // GET /wc/{match}/resultado  [auth]
    // - Carga el Answer del usuario para este match
    // - OG meta tags con la predicción para compartir
    // - Vista: resultado.blade.php
    public function resultado(FootballMatch $match) { ... }

    // GET /wc/
    // - Partidos del día con status Not Started
    // - Vista: hoy.blade.php
    public function hoy() { ... }
}
```

---

## 🎨 Diseño — `landing.blade.php`

### Estructura visual

```
┌──────────────────────────────────────┐
│  [Imagen de fondo: estadio WC 2026]  │
│  [Overlay oscuro semi-transparente]  │
│                                      │
│        ⚽ FIFA World Cup 2026        │
│     🏟️  Grupo A · Jun 11 · 19:00   │
│                                      │
│   ┌──────────┐    ┌──────────┐      │
│   │ MÉXICO   │ vs │  S.ÁFRICA│      │
│   │  🇲🇽    │    │    🇿🇦  │      │
│   └──────────┘    └──────────┘      │
│                                      │
│   ┌──────────────────────────────┐  │
│   │  ⚽  Predice el resultado    │  │  ← botón gold/amarillo
│   └──────────────────────────────┘  │
│                                      │
│   [si autenticado: opciones de voto] │
│   [si guest: form de username]       │
└──────────────────────────────────────┘
```

### Estados de la landing

**A. Guest (sin auth):**
- Botón CTA visible
- Al hacer clic → muestra inline el formulario de username (con Alpine.js `x-show`)
- Form hace POST a `/wc/{match}/auth`
- No redirige a otra página (todo en la misma vista)

**B. Autenticado, sin voto:**
- En lugar del CTA muestra directamente las 3 opciones de predicción
- Cada opción es un botón grande con el nombre del equipo / "Empate"
- Al hacer clic → POST `/wc/{match}/votar`

**C. Autenticado, ya votó:**
- Muestra su predicción resaltada (sin poder cambiarla si el partido ya empezó)
- Botón "Compartir tu predicción"

---

## 🏆 Diseño — `resultado.blade.php`

### Share Card (generada en el cliente con CSS/Canvas)

```
┌─────────────────────────────────────┐
│        ⚽ FIFA World Cup 2026        │
│                                      │
│    MÉXICO 🇲🇽 vs 🇿🇦 SUDÁFRICA    │
│         Jun 11 · 19:00 UTC           │
│                                      │
│          🏅 Mi predicción:           │
│    ┌──────────────────────────┐      │
│    │   🥇  Victoria México   │      │  ← opción elegida (fondo gold)
│    └──────────────────────────┘      │
│                                      │
│    Jugado en offsideclub.com ⚽      │
└─────────────────────────────────────┘
```

**Acciones:**
- `📤 Compartir` → `navigator.share()` (Web Share API) con texto + URL `/wc/{match}`
- `📋 Copiar enlace` → fallback para desktop
- `⚽ Predice más partidos` → `/wc/` (lista del día)
- `🏆 Ver mi ranking` → `/groups/{wcGroupCode}` (grupo público WC)

---

## ⚙️ Lógica de Negocio

### Encontrar la Question del partido en el grupo WC

```php
// En WorldCupSocialController::landing()
$wcGroup = Group::worldCup()->first();

$question = Question::where('group_id', $wcGroup->id)
    ->where('football_match_id', $match->id)
    ->where('template_question_id', 44)
    ->with('options')
    ->first();

// Si no existe aún, crearla on-the-fly (reutiliza lógica de fillWorldCupPredictiveQuestions)
if (!$question) {
    $question = $this->createWCQuestionForMatch($wcGroup, $match);
}
```

### Login rápido (sin Google, solo username)

```php
// POST /wc/{match}/auth
// Idéntico a LoginController@login
// Diferencia: redirect → back a /wc/{match}
session()->put('url.intended', route('wc.match', $match));
// ... usar LoginController@login logic ...
return redirect()->intended(route('wc.match', $match));
```

### Registro del voto

```php
// POST /wc/{match}/votar
// 1. Validar option_id pertenece a la question del partido
// 2. Answer::updateOrCreate([user_id, question_id], [question_option_id, ...])
// 3. Flash: opción elegida
// 4. Redirect /wc/{match}/resultado
```

---

## 🔗 URL de Compartir

El formato de URL a poner en las stories:

```
https://offsideclub.es/wc/{matchId}
```

Ejemplo: `https://offsideclub.es/wc/1` para México vs Sudáfrica.

> **OG Tags** en la landing para que la preview del link en RRSS se vea bien:
> - `og:title`: "⚽ México vs Sudáfrica · Predice ahora"
> - `og:image`: imagen del partido (puede ser la imagen del Mundial de fondo)
> - `og:description`: "¿Quién ganará? Predice el resultado del partido y compite"

---

## 🔒 Seguridad

- Las rutas `votar` y `resultado` requieren `auth` middleware
- El `quickAuth` incluye CSRF token en el form (no se excluye del middleware)
- Rate-limit en `quickAuth` igual que en `/login`
- La landing es completamente pública (sin datos sensibles de otros usuarios)
- El `matchId` debe ser de la competición WC (`competition_id = 28`) — validar en el controller

---

## 📅 Plan de Implementación

| Fase | Tarea | Estimado |
|------|-------|----------|
| 1 | `WorldCupSocialController` + rutas | 2h |
| 2 | `landing.blade.php` (diseño + Alpine.js states) | 3h |
| 3 | `resultado.blade.php` + share card CSS | 2h |
| 4 | `hoy.blade.php` (lista partidos del día) | 1h |
| 5 | OG meta tags + preview RRSS | 30min |
| 6 | Tests + ajustes responsive | 1h |
| **Total** | | **~9.5h** |

---

## 🚀 Comandos de Arranque

```bash
# Cuando se aprueba el plan:
php artisan make:controller WorldCupSocialController
npm run build-views
php artisan cache:clear
```

---

## 💡 Mejoras Opcionales (post-MVP)

- **Imagen OG dinámica**: generar imagen del partido con el score predicho server-side (`Intervention Image`) para que la preview del link en WhatsApp/Instagram sea espectacular
- **Share card descargable**: botón "Descargar imagen" con html2canvas o dom-to-image
- **Counter de predicciones**: "X personas predicen victoria de México" (visible para todos, sin login)
- **Deep link a la app**: si el usuario tiene la app instalada, abrir directo en el grupo WC
