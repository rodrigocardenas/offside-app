# 🎮 Pre Match Frontend Components - Documentation

## Visión General

El módulo Pre Match cuenta con componentes Blade reutilizables para crear desafíos en partidos, proponer acciones improbables y validar resultados.

## 📦 Componentes Disponibles

### 1. **Pre Match Card** (`card.blade.php`)
Tarjeta principal que muestra un desafío Pre Match.

**Uso:**
```blade
<x-pre-match.card 
    :preMatch="$preMatch"
    :match="$preMatch->match"
/>
```

**Props:**
- `preMatch`: Instancia de `PreMatch` model
- `match`: Instancia de `Match` asociado

**Características:**
- Muestra información del partido
- Tipo y descripción del castigo
- Barra de progreso de propuestas aceptadas
- Botones de acción dinámicos según estado

**Estados:**
- `DRAFT` - Modo borrador (solo editable)
- `OPEN` - Abierto para propuestas
- `LOCKED` - Cerrado (no hay nuevas propuestas)
- `RESOLVED` - Resuelto (mostrar castigos)

---

### 2. **Proposition Item** (`proposition-item.blade.php`)
Elemento individual de una propuesta con votación.

**Uso:**
```blade
@forelse($preMatch->propositions as $proposition)
    <x-pre-match.proposition-item 
        :proposition="$proposition"
    />
@endforelse
```

**Props:**
- `proposition`: Instancia de `PreMatchProposition` model

**Características:**
- Avatar y nombre del propositor
- Badge de probabilidad (🟢 alta, 🟡 media, 🔴 baja)
- Barra de aprobación dinámica
- Botones de voto (solo si no ha votado)
- Indicador del propio voto

**Lógica de Votación:**
```typescript
// El componente previene:
// - Que el propositor vote su propia propuesta
// - Votos duplicados
// - Votos después de que sea ACCEPTED/REJECTED
```

---

### 3. **Create Proposal Modal** (`create-proposal-modal.blade.php`)
Modal para crear nuevas propuestas con sugerencias automáticas.

**Uso:**
```blade
<x-pre-match.create-proposal-modal />

<!-- Abrir con JavaScript -->
<script>
    openProposalModal(preMatchId);
</script>
```

**Características:**
- ✅ Texto libre para propuestas personalizadas
- 🎲 Botón "Sugerir acción aleatoria"
- 📊 Tags de probabilidad y categoría
- 📝 Descripción opcional
- ✅/❌ Botones de acción

**Flujo Modal:**
1. Usuario abre modal clickeando "Nueva Acción"
2. Puede escribir acción libre O usar 🎲 Sugerir
3. Si sugiere, ve la acción + probabilidad + tags
4. Puede aceptar la sugerencia o pedir otra
5. Submitea el formulario para crear

**API Endpoints Usados:**
- `GET /api/action-templates/random` - Obtener sugerencia
- `POST /api/pre-matches/{id}/propositions` - Crear propuesta

---

### 4. **Resolution Modal** (`resolution-modal.blade.php`)
Modal de admin para validar si se cumplió la acción (requiere `auth:admin`).

**Uso:**
```blade
@if(Auth::user()?->isAdmin())
    <x-pre-match.resolution-modal />
@endif
```

**Características:**
- ⚖️ Validation visual de votación del grupo
- ✅/❌ Radio buttons para decisión admin
- 📝 Notas con evidencia (links, timestamps)
- 👤 Selector de "perdedor" si rechaza
- 🔧 Formulario con validación

**Flujo Resolución:**
1. Admin abre modal de proposición
2. Revisa:
   - Acción propuesta
   - Votos del grupo (%)
   - Descripción de evidencia
3. Selecciona: ✅ Se cumplió O ❌ No se cumplió
4. Si NO se cumplió:
   - Selecciona quién pierde (propositor)
   - Admin anota por qué
5. Submenu aplica resolución y penalidad

**API Endpoints Usados:**
- `POST /api/pre-matches/{id}/resolve` - Validar acción

---

### 5. **Penalty History/Leaderboard** (`penalty-history.blade.php`)
Historial de castigos con filtros y contador de deudas.

**Uso:**
```blade
<x-pre-match.penalty-history />

<!-- En JavaScript, cargar penalidades: -->
<script>
    loadPenalties(groupId);
</script>
```

**Características:**
- 📊 Filtros por estado (Pendientes/Cumplidos)
- 🏷️ Filtros por tipo (Puntos/Social/Venganza)
- 💔 Detalles según tipo
- ✅ Botón marcar como cumplido
- 🎨 Código de colores por tipo

**Tipos de Castigos:**
```
🔴 POINTS     - Restar puntos del ranking
🟠 SOCIAL     - Castigos sociales (fotos, etc)
🟣 REVENGE    - Retos de venganza contra otro usuario
```

**API Endpoints Usados:**
- `GET /api/pre-matches/{id}/penalties` - Listar castigos
- `PATCH /api/penalties/{id}/fulfill` - Marcar cumplido

---

## 🎨 TypeScript Client Helper

Archivo: `resources/js/pre-match-client.ts`

Clase global `PreMatchClient` que maneja toda la lógica:

```typescript
// Uso global en window
window.preMatchClient.loadPreMatches(groupId)
window.preMatchClient.createPreMatch(...)
window.preMatchClient.addProposition(...)
window.preMatchClient.voteProposition(propId, approved)
window.preMatchClient.resolvePreMatch(...)
window.preMatchClient.getSuggestedAction()
window.preMatchClient.loadPenalties(groupId)
window.preMatchClient.markPenaltyFulfilled(penaltyId)
```

**Características:**
- ✅ Manejo centralizado de API calls
- 🔔 Notificaciones automáticas
- 📡 WebSocket integration ready
- 🔄 Auto-refresh mechanism
- 🛡️ CSRF token handling

---

## 📄 Vista de Integración

Archivo: `resources/views/pre-match/group.blade.php`

Ejemplo completo integrando todos los componentes:

```blade
<!-- Stats Cards -->
<div class="grid grid-cols-4 gap-4">
    <!-- Total, Abiertos, Resueltos, Castigos pendientes -->
</div>

<!-- Tabs de Filtro -->
<!-- Filter: Todos / Abiertos / Cerrados / Resueltos -->

<!-- Lista de Pre Matches -->
@forelse($preMatches as $preMatch)
    <x-pre-match.card :preMatch="$preMatch" />
@endforelse

<!-- Historial de Castigos -->
<x-pre-match.penalty-history />

<!-- Modales -->
<x-pre-match.create-proposal-modal />
@if(Auth::user()?->isAdmin())
    <x-pre-match.resolution-modal />
@endif
```

---

## 🎯 Flujos de Usuario

### 👤 Flujo Miembro Regular

```
1. Ver desafío abierto (card)
   ↓
2. Hacer clic "Nueva Acción"
   ↓
3. Modal: Escribir acción o usar 🎲
   ↓
4. Submit propuesta
   ↓
5. Ver propuesta en lista (proposition-item)
   ↓
6. Votar otras propuestas ✅/❌
   ↓
7. Ver historial de castigos
```

### 👮 Flujo Admin

```
1. Expandir Pre Match resuelto
   ↓
2. Ver proposiciones con votación
   ↓
3. Click "Validar" en proposición
   ↓
4. Resolution modal:
      - ¿Se cumplió?
      - Notas con evidencia
      - Seleccionar perdedor
   ↓
5. Submit validación
   ↓
6. Crear `GroupPenalty` automáticamente
   ↓
7. Aparece en penalty history
```

---

## 🔌 Integración API

### GET `/api/action-templates`
```json
{
    "data": [
        {
            "id": 1,
            "action": "3 goles de cabeza",
            "description": "Raro pero posible",
            "probability": 0.15,
            "category": "GOALS"
        }
    ]
}
```

### GET `/api/action-templates/random`
Retorna UN action template basado en weighted probability.

### POST `/api/pre-matches/{id}/propositions`
```json
{
    "action": "3 goles de cabeza",
    "description": "Opcional"
}
```

### POST `/api/pre-match-propositions/{id}/vote`
```json
{
    "approved": true // o false
}
```

### POST `/api/pre-matches/{id}/resolve`
```json
{
    "proposition_id": 123,
    "was_fulfilled": true,
    "admin_notes": "Video en 2:34...",
    "loser_user_id": 456 // null si fue cumplido
}
```

---

## 🌙 Dark Mode

Todos los componentes soportan dark mode usando Tailwind:

```html
<!-- Ejemplo -->
<div class="bg-white dark:bg-gray-800">
    <p class="text-gray-900 dark:text-white">
        Contenido
    </p>
</div>
```

---

## 🧪 Testing

### Blade Components (Unit)
```php
// tests/Unit/PreMatchComponentTest.php
test('pre-match-card renders with status badge', function() {
    // ...
});
```

### API Integration (Feature)
```php
// tests/Feature/PreMatchTest.php
test('can create proposition', function() {
    $response = $this->post('/api/pre-matches/1/propositions', [
        'action' => 'Test',
    ]);
    $response->assertStatus(201);
});
```

---

## 📋 Checklist de Implementación

- [ ] Componentes Blade creados
- [ ] ActionTemplateController implementado
- [ ] Rutas API registradas
- [ ] TypeScript client compilado
- [ ] Vistas de integración creadas
- [ ] Tests unitarios agregados
- [ ] Tests de integración agregados
- [ ] WebSocket configurado (opcional)
- [ ] Dark mode verificado
- [ ] Documentación completada

---

## 🐛 Troubleshooting

### Modal no se abre
```javascript
// Verificar que el elemento con id existe
console.log(document.getElementById('createProposalModal'));

// Verificar preMatchId está seteado
console.log(document.getElementById('preMatchId')?.value);
```

### Votación no funciona
```javascript
// Verificar CSRF token
console.log(document.querySelector('meta[name="csrf-token"]'));

// Ver error en consola
// Network tab → POST /api/pre-match-propositions/{id}/vote
```

### Sugerencia aleatoria falla
```javascript
// Verificar ActionTemplateController está cargado
fetch('/api/action-templates/random')
    .then(r => console.log(r.status, r));
```

---

**Última actualización:** Sprint 2 - Frontend Components
**Estado:** En desarrollo
**Rama:** `feature/pre-match-module`
