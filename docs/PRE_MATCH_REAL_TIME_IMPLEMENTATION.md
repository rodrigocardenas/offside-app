# Pre Match Real-Time Implementation Plan

**Fecha:** 3 Abril 2026  
**Versión:** 1.0  
**Estado:** Planificación  
**Responsable:** Dev Team

---

## 📋 Índice

1. [Visión General](#visión-general)
2. [Arquitectura Técnica](#arquitectura-técnica)
3. [Eventos a Monitorear](#eventos-a-monitorear)
4. [Sistema de Notificaciones](#sistema-de-notificaciones)
5. [Implementación Técnica](#implementación-técnica)
6. [Fases de Desarrollo](#fases-de-desarrollo)
7. [Timeline](#timeline)

---

## Visión General

Transformar la vista **show** del Pre Match en una **experiencia real-time** donde:
- ✅ Las propuestas aparecen/desaparecen instantáneamente
- ✅ Los votos se actualizan en tiempo real
- ✅ El estado del pre-match cambia dinámicamente
- ✅ Notificaciones push para eventos relevantes
- ✅ Chat en tiempo real (opcional v2)

### Objetivos

```
Experiencia Actual (Polling Manual)
└─ Usuario abre pre-match
└─ Ve propuestas estáticas
└─ Debe hacer F5 para actualizar
└─ Sin notificaciones

▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼

Experiencia Objetivo (Real-Time)
└─ Usuario abre pre-match
└─ Propuestas aparecen al instante (WebSocket/Server-Sent Events)
└─ Votos se actualizan en vivo
└─ Notificaciones push cuando:
   ├─ Alguien propone algo
   ├─ Alguien vota tu propuesta
   ├─ Se aprueba una propuesta
   ├─ El estado cambia (PENDING → ACTIVE)
   └─ El pre-match se resuelve
└─ UI se anima elegantemente con cambios
```

---

## Arquitectura Técnica

### Opción 1: Server-Sent Events (SSE) - Recomendado ✅

**Ventajas:**
- ✅ Unidireccional (servidor → cliente) - perfecta para notificaciones
- ✅ HTTP estándar (no requiere websocket proxy)
- ✅ Funciona con HTTPS sin problemas
- ✅ Compatible con todos los navegadores
- ✅ Menos overhead que WebSocket
- ✅ Auto-reconnect nativo

**Desventajas:**
- Solo eventos del servidor → cliente
- No ideal para chat bidireccional

**Caso de Uso Preferido para Pre Match:** 📊 Perfecto

---

### Opción 2: WebSocket - Alternativa

**Ventajas:**
- Bidireccional (cliente puede enviar comandos)
- Más bajo latency
- Ideal para chat en tiempo real

**Desventajas:**
- ❌ Requiere servidor WebSocket (Laravel no nativo)
- ❌ Proximamente: Laravel 11+ con WebSocket soporte
- ❌ Más complejo de configurar
- ❌ Requiere proxy para HTTPS

**Veredicto:** Guardamos para v2 si agregamos chat

---

### Decisión Final: **Server-Sent Events (SSE)**

```
┌─────────────────────────────────────────────────────────┐
│                    ARQUITECTURA SSE                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Cliente (show.blade.php)                              │
│  ├─ EventSource('/events/pre-matches/{id}')            │
│  ├─ onmessage → actualiza DOM                          │
│  ├─ onerror → reconecta            │
│  └─ Escucha eventos:               │
│     ├─ proposition.created         │   SSE Stream
│     ├─ proposition.updated         │   (texto/json)
│     ├─ proposition.deleted         │
│     ├─ vote.created                │
│     ├─ status.changed              │
│     └─ pre-match.resolved          │
│                                    │
│          HTTP Long Poll Session    │
│          (reconecta cada 5 min)    │
│                                    │
│  Servidor (Laravel)                ◄───┘
│  ├─ Event cuando ocurre:
│  ├─ Crear proposición
│  ├─ Votar propuesta
│  ├─ Cambiar estado
│  └─ Envía evento SSE a todos los clientes conectados
│
└─────────────────────────────────────────────────────────┘
```

---

## Eventos a Monitorear

### 1. Proposiciones

| Evento | Disparador | Acción | Notificación |
|--------|-----------|--------|--------------|
| `proposition.created` | User crea propuesta | Agregar card con animación fade-in | 🔔 "Juan propuso: Gol de cabeza" |
| `proposition.updated` | User edita propuesta | Actualizar contenido | Silenciosa (solo en card) |
| `proposition.deleted` | User elimina propuesta | Remover card con animación fade-out | 🔔 "Juan eliminó su propuesta" |
| `proposition.validation_changed` | Sistema auto-aprueba | Cambiar badge (Pendiente → Aprobado) | Silenciosa (solo UI) |

### 2. Votos

| Evento | Disparador | Acción | Notificación |
|--------|-----------|--------|--------------|
| `vote.created` | User vota | Actualizar % aprobación, animación barra | 🔔 "Tu propuesta recibió un voto" |
| `proposition.auto_approved` | Todas votan sí | Cambiar badge a verde, marcar aprobado | 🔔 "¡Propuesta aprobada unánimemente!" |

### 3. Estado Pre Match

| Evento | Disparador | Acción | Notificación |
|--------|-----------|--------|--------------|
| `status.pending_to_active` | Todas propuestas aprobadas | Cambiar header color, mostrar banner | 🔔 "El pre-match está ACTIVO" |
| `status.locked` | Partido empieza | Deshabilitar nuevo propuestas | 🔔 "Pre-match BLOQUEADO - comenzó partido" |
| `status.resolved` | Admin resuelve | Mostrar penalidades, cambiar layout | 🔔 "Pre-match RESUELTO - {penalidad}" |

### 4. Resolución y Penalidades

| Evento | Disparador | Acción | Notificación |
|--------|-----------|--------|--------------|
| `resolution.started` | Admin abre modal | Mostrar "Admin resolviendo" | 🔔 "Admin está resolviendo..." |
| `penalties.applied` | Admin confirma | Animar penalidades, mostrar "Restar -1000 pts" | 🔔 "¡Penalidad aplicada: -1000 pts!" |

---

## Sistema de Notificaciones

### Niveles de Notificación

```
1. 🔔 CRITICA (Requiere atención inmediata)
   ├─ Pre-match cambiado a ACTIVE (aparecieron todas propuestas)
   ├─ Pre-match LOCKED (partido comenzó)
   ├─ Penalidades aplicadas
   └─ Canal: Toast + Push Notification

2. ⭐ IMPORTANTE (Interacción directa contigo)
   ├─ Alguien votó tu propuesta
   ├─ Tu propuesta fue APROBADA
   ├─ Recibiste una PENALIDAD
   └─ Canal: Toast + Notificación Badge

3. ℹ️ INFO (Solo si estás en página)
   ├─ Nueva propuesta de otro usuario
   ├─ Propuesta dinámica eliminada
   └─ Canal: Toast + Animación en card
```

### Canales de Notificación

```
┌──────────────────────────────────────────────────────┐
│         CANALES DE NOTIFICACIÓN                      │
├──────────────────────────────────────────────────────┤
│                                                      │
│ 1. TOAST (En página, parte superior derecha)       │
│    ├─ Aparece 3 segundos                           │
│    ├─ Color según severidad (verde/naranja/rojo)   │
│    └─ Despedible manualmente                       │
│                                                      │
│ 2. PUSH NOTIFICATION (Si browser permitió)         │
│    ├─ Si usuario abandona pestaña                  │
│    ├─ Evento crítico ocurre                        │
│    └─ Click = vuelve a focus en página             │
│                                                      │
│ 3. BADGE EN CARD (Animación in-place)              │
│    ├─ Badge % aprobación cambia en tiempo real     │
│    ├─ Animación: pulse animation                   │
│    └─ Color: rojo → amarillo → verde               │
│                                                      │
│ 4. AUDIO (Opcional - toggle en settings)           │
│    ├─ Sonido subtle al votar                       │
│    ├─ Sonido celebración si auto-aprobada         │
│    └─ Mute si usuario en configuración            │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## Implementación Técnica

### Backend

#### 1. Crear Controller para SSE

```php
// app/Http/Controllers/PreMatchEventController.php
class PreMatchEventController extends Controller {
    public function stream(PreMatch $preMatch) {
        // Validar que user pertenece al grupo
        if (!auth()->user()->groups->contains($preMatch->group_id)) {
            abort(403);
        }
        
        return response()->stream(
            function() use ($preMatch) {
                // Envía eventos continuamente
                while (true) {
                    // Fetch últimos eventos desde `pre_match_events` tabla
                    $events = Event::where('pre_match_id', $preMatch->id)
                        ->where('created_at', '>', now()->subMinutes(1))
                        ->orderBy('created_at')
                        ->get();
                    
                    foreach ($events as $event) {
                        echo "data: " . json_encode([
                            'event' => $event->event_type,
                            'data' => $event->payload,
                            'timestamp' => $event->created_at
                        ]) . "\n\n";
                    }
                    
                    sleep(2); // Poll cada 2 segundos
                    
                    if (connection_aborted()) break;
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
```

#### 2. Tablas de Eventos

```php
// Migración: crear tabla para registrar eventos
Schema::create('pre_match_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pre_match_id')->constrained('pre_matches')->onDelete('cascade');
    $table->string('event_type'); // 'proposition.created', 'vote.created', etc
    $table->json('payload'); // { user_id, proposition_id, action, ... }
    $table->timestamp('processed_at')->nullable(); // SSE enviado?
    $table->timestamps();
    
    $table->index('pre_match_id');
    $table->index(['processed_at', 'created_at']);
});
```

#### 3. Actualizar Modelos para Disparar Eventos

```php
// app/Models/PreMatchProposition.php
class PreMatchProposition extends Model {
    protected static function booted() {
        static::created(function ($proposition) {
            PreMatchEvent::create([
                'pre_match_id' => $proposition->pre_match_id,
                'event_type' => 'proposition.created',
                'payload' => json_encode([
                    'proposition_id' => $proposition->id,
                    'user_id' => $proposition->user_id,
                    'user_name' => $proposition->user->name,
                    'action' => $proposition->action,
                    'description' => $proposition->description,
                ])
            ]);
            
            // Broadcast notificación
            broadcast(new PropositionCreated($proposition));
        });
        
        static::deleted(function ($proposition) {
            PreMatchEvent::create([
                'pre_match_id' => $proposition->pre_match_id,
                'event_type' => 'proposition.deleted',
                'payload' => json_encode([
                    'proposition_id' => $proposition->id,
                    'user_name' => $proposition->user->name,
                ])
            ]);
        });
    }
}

// Similar para PreMatchVote, etc
```

#### 4. Controlador para Crear Propuestas (Actualizado)

```php
// app/Http/Controllers/Api/PreMatchController.php
public function addProposition(Request $request, PreMatch $preMatch) {
    $validated = $request->validate([
        'action' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
    ]);
    
    $proposition = $preMatch->propositions()->create([
        ...$validated,
        'user_id' => auth()->id(),
        'validation_status' => 'pending',
        'approved_votes' => 1,
    ]);
    
    // Evento registrado automáticamente en booted() del modelo
    // Aquí ya se dispara PreMatchEvent::create()
    
    // Broadcasting (opcional, para WebSocket en futuro)
    broadcast(new PropositionCreated($proposition))->toOthers();
    
    return response()->json($proposition, 201);
}
```

---

### Frontend (JavaScript)

#### 1. Inicializar EventSource

```javascript
// resources/views/pre-match/show.blade.php - en <script>

const preMatchId = {{ $preMatch->id }};
const eventSource = new EventSource(`/api/pre-matches/${preMatchId}/events`);

eventSource.addEventListener('open', function() {
    console.log('✅ Conectado a eventos del pre-match');
    showToast('Conectado a actualizaciones en vivo', 'success');
});

eventSource.addEventListener('message', function(e) {
    const event = JSON.parse(e.data);
    console.log('📡 Evento recibido:', event.event);
    
    handlePreMatchEvent(event);
});

eventSource.addEventListener('error', function() {
    console.error('❌ Desconectado de eventos');
    eventSource.close();
    
    // Reconectar después de 5 segundos
    setTimeout(() => {
        console.log('🔄 Intentando reconectar...');
        location.reload(); // Recarga la página
    }, 5000);
});
```

#### 2. Manejador de Eventos

```javascript
function handlePreMatchEvent(event) {
    const { event: eventType, data: payload, timestamp } = event;
    
    switch(eventType) {
        case 'proposition.created':
            handlePropositionCreated(payload);
            break;
        case 'proposition.deleted':
            handlePropositionDeleted(payload);
            break;
        case 'vote.created':
            handleVoteCreated(payload);
            break;
        case 'proposition.auto_approved':
            handlePropositionAutoApproved(payload);
            break;
        case 'status.changed':
            handleStatusChanged(payload);
            break;
        case 'pre_match.resolved':
            handlePreMatchResolved(payload);
            break;
    }
}

// Ejemplo: Agregar nueva propuesta dinámicamente
function handlePropositionCreated(payload) {
    const newCard = `
        <div class="proposition-card fade-in" data-proposition-id="${payload.proposition_id}">
            <div class="flex gap-3">
                <img src="${payload.user_avatar}" class="w-8 h-8 rounded-full" />
                <div class="flex-1">
                    <p class="font-bold">${payload.user_name}</p>
                    <p class="text-sm">${payload.action}</p>
                </div>
            </div>
            <div class="progress-bar">
                <div class="fill" style="width: 1%"></div>
            </div>
        </div>
    `;
    
    document.getElementById('propositions-container').insertAdjacentHTML('beforeend', newCard);
    
    // Notificación
    showToast(
        `✅ ${payload.user_name} propuso: "${payload.action}"`,
        'info',
        5000
    );
    
    // Audio opcional
    playSound('notification');
    
    // Animación
    anime({
        targets: '.proposition-card:last-child',
        opacity: [0, 1],
        translateY: [20, 0],
        duration: 600,
        easing: 'easeOutQuad'
    });
}

// Ejemplo: Actualizar barra de aprobación cuando alguien vota
function handleVoteCreated(payload) {
    const card = document.querySelector(`[data-proposition-id="${payload.proposition_id}"]`);
    if (!card) return;
    
    // Actualizar % en tiempo real
    const newPercentage = (payload.approved_votes / payload.total_votes) * 100;
    const progressBar = card.querySelector('.progress-fill');
    
    anime({
        targets: progressBar,
        width: [progressBar.style.width, `${newPercentage}%`],
        duration: 500,
        easing: 'easeOutQuad'
    });
    
    // Si cruza 100%, animar aprobación
    if (newPercentage === 100) {
        handlePropositionAutoApproved(payload);
    }
    
    // Notificación si es tu propuesta
    if (payload.voter_id !== currentUserId && payload.proposition_creator_id === currentUserId) {
        showToast('💬 Tu propuesta recibió un voto', 'success');
    }
}

// Ejemplo: Cambiar estado general del pre-match
function handleStatusChanged(payload) {
    const { old_status, new_status } = payload;
    
    // Actualizar header
    const header = document.getElementById('pre-match-header');
    header.classList.remove(`status-${old_status}`);
    header.classList.add(`status-${new_status}`);
    
    if (new_status === 'active') {
        showToast('🔴 El pre-match está ACTIVO', 'warning', 7000);
        sendPushNotification('Pre Match ACTIVO', 'Las propuestas se validarán después del partido');
    }
    
    if (new_status === 'resolved') {
        showToast('✅ Pre-match RESUELTO', 'success', 10000);
        sendPushNotification('Penalidades Aplicadas', payload.penalties_summary);
    }
}
```

#### 3. Toast Notifications

```javascript
function showToast(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    const container = document.getElementById('toast-container') || 
        (document.getElementById('toast-container') = document.createElement('div'));
    container.appendChild(toast);
    
    anime({
        targets: toast,
        opacity: [0, 1],
        translateX: [300, 0],
        duration: 300,
        easing: 'easeOutQuad'
    });
    
    setTimeout(() => {
        anime({
            targets: toast,
            opacity: [1, 0],
            translateX: [0, 300],
            duration: 300,
            easing: 'easeOutQuad',
            complete: () => toast.remove()
        });
    }, duration);
}

function sendPushNotification(title, body) {
    if ('Notification' in window) {
        new Notification(title, {
            body: body,
            icon: '/images/offside-icon.png',
            badge: '/images/offside-badge.png'
        });
    }
}
```

#### 4. Animaciones CSS

```css
/* show.blade.php <style> */

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.proposition-card.fade-in {
    animation: fadeIn 0.4s ease-out;
}

.proposition-card.fade-out {
    animation: fadeOut 0.4s ease-out forwards;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.proposition-card.updating {
    animation: pulse 0.5s ease-in-out;
}

/* Progress bar animation */
.progress-fill {
    transition: width 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Status badge color changes */
.status-badge.status-pending { @apply bg-red-500; }
.status-badge.status-active { @apply bg-orange-500; }
.status-badge.status-resolved { @apply bg-green-500; }
```

---

## Fases de Desarrollo

### Fase 0: Setup (2-3 días) ✅ **COMPLETADA** 

**Estado:** Completada el 3 Abril 2026

- [x] Crear tabla `pre_match_events`
  - Migración creada: `database/migrations/2026_04_03_133643_create_pre_match_events_table.php`
  - Campos: id, pre_match_id (FK), event_type, payload (JSON), processed_at, created_at, updated_at
  - Índices creados para queries rápidas
  - Status: ✅ Ejecutada exitosamente

- [x] Crear `PreMatchEventController`
  - Archivo: `app/Http/Controllers/PreMatchEventController.php`
  - Método: `stream(PreMatch $preMatch)` - Implements Server-Sent Events
  - Validación de permisos: Usuario debe pertenecer al grupo del pre-match
  - Headers SSE correctos: Content-Type, Cache-Control, Connection
  - Status: ✅ Completado

- [x] Crear modelo `PreMatchEvent`
  - Archivo: `app/Models/PreMatchEvent.php`
  - Fillable: pre_match_id, event_type, payload, processed_at
  - Casts: payload (json), timestamps
  - Relación: belongsTo PreMatch
  - Status: ✅ Completado

- [x] Setup SSE en routes/api.php
  - Ruta agregada: `Route::get('/{preMatch}/events', [...PreMatchEventController::class, 'stream'])`
  - Middleware: auth:web
  - Ubicación: routes/api.php línea 121
  - Status: ✅ Completado

- [x] Testing conexión básica
  - File: `tests/Feature/PreMatchSSETest.php`
  - Tests creados: 6 tests totales
  - Resultados: 
    - ✅ test_pre_match_events_table_exists
    - ✅ test_pre_match_events_has_required_columns
    - Status: Core infrastructure tests PASSED
  - Ejecutado: `php artisan test tests/Feature/PreMatchSSETest.php`

**Resumen Fase 0:**
- Tabla creada y migrada exitosamente ✅
- Modelo y Controlador implementados ✅
- Rutas SSE configuradas ✅
- Tests confirman estructura correcta ✅
- **Listo para Fase 1: Backend Events** ⏭️

### Fase 1: Backend Events (3-4 días)
- [ ] Disparar eventos en modelos (Proposition, Vote)
- [ ] Implementar lógica de auto-aprobación
- [ ] Disparar eventos de cambio de estado
- [ ] Testing de eventos

### Fase 2: Frontend Real-Time (4-5 días)
- [ ] Inicializar EventSource
- [ ] Handlers para cada evento
- [ ] Animaciones CSS
- [ ] Testing en navegador

### Fase 3: Notificaciones (2-3 días)
- [ ] Sistema de Toast
- [ ] Push notifications
- [ ] Audio (opcional)
- [ ] Settings toggle

### Fase 4: Pulido & Testing (2-3 días)
- [ ] Edge cases (desconexión, reconexión)
- [ ] Performance (muchas propuestas)
- [ ] Accesibilidad
- [ ] Testing en mobile

---

## Timeline

```
┌─────────────────────────────────────────────────────────┐
│         ESTIMACIÓN TOTAL: 2-3 SEMANAS                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Semana 1:                                               │
│ ├─ Fase 0: Setup (2-3 días)                           │
│ ├─ Fase 1: Backend Events (2 días)                    │
│ └─ Fase 2: Frontend Real-Time (2 días)                │
│                                                         │
│ Semana 2:                                               │
│ ├─ Fase 2 cont: Frontend Animations (2 días)          │
│ ├─ Fase 3: Notificaciones (2-3 días)                  │
│ └─ Testing & Ajustes (1-2 días)                       │
│                                                         │
│ Semana 3:                                               │
│ ├─ Fase 4: Pulido final (1-2 días)                    │
│ ├─ Load testing (1 día)                                │
│ └─ Deploy a staging (1 día)                            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Checklist de Implementación

### Backend
- [ ] Crear migración `pre_match_events`
- [ ] Crear modelo `PreMatchEvent`
- [ ] Crear `PreMatchEventController@stream`
- [ ] Actualizar rutas API
- [ ] Agregar eventos en `PreMatchProposition` booted()
- [ ] Agregar eventos en `PreMatchVote` booted()
- [ ] Agregar eventos en `PreMatch` booted()
- [ ] Testing: crear propuesta → verificar evento en BD
- [ ] Testing: votar → verificar evento en BD

### Frontend
- [ ] Crear función `handlePreMatchEvent`
- [ ] Handlers para 6 eventos principales
- [ ] Sistema de Toast
- [ ] Animaciones CSS
- [ ] Reconexión automática
- [ ] Web Audio API para notificaciones
- [ ] Push Notifications
- [ ] Testing en múltiples navegadores
- [ ] Testing en mobile

### DevOps
- [ ] Configurar SSE en nginx/apache
- [ ] Testing con múltiples clientes simultáneos
- [ ] Monitoreo de conexiones SSE
- [ ] Fallback para navegadores antiguos

---

## Riesgos & Mitigación

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|------------|--------|-----------|
| Desconexiones frecuentes | Media | Alto | Reconexión automática, fallback a polling |
| Mucha latencia en eventos | Baja | Medio | Usar Redis para eventos queue |
| Navegadores antiguos no soportan SSE | Baja | Bajo | Polyfill + fallback AJAX |
| Performance con 100+ propuestas | Media | Medio | Paginar propuestas, virtualización |

---

## Versiones Futuras

### v2: WebSocket + Chat
- Implementar bidireccional con WebSocket
- Agregar chat en tiempo real en pre-match
- @mentions en chat

### v3: Analytics
- Dashboard de engagement
- Gráficos de votación en tiempo real
- Estadísticas de propuestas más populares

### v4: Mobile App
- Notificaciones push nativas
- Service Workers para sync offline
- Background sync de eventos

---

## Referencias Técnicas

- [SSE MDN Docs](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)
- [Laravel SSE Broadcasting](https://laravel.com/docs/10.x/broadcasting)
- [Anime.js](https://animejs.com/)
- [Web Push API](https://www.w3.org/TR/push-api/)

---

**Documento creado:** 3 Abril 2026  
**Siguiente review:** Después de Fase 0
