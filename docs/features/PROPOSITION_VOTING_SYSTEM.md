# Sistema de Votación de Proposiciones - Pre Match

## 📋 Planificación de Tarea

**Objetivo:** Implementar un sistema visual de aprobación/desaprobación de proposiciones con avatares de usuarios

---

## 🎯 Requisitos Funcionales

### Actual (Estado Previo)
- ✅ Votos de aprobación (👍)
- ✅ Contador de aprobaciones
- ❌ Votos de desaprobación (👎)
- ❌ Visualización de desaprobadores
- ❌ Avatares de usuarios

### Nuevo (A Implementar)
- ✅ Botón de desaprobación (👎) junto a aprobación
- ✅ Mostrar avatares de aprobadores
- ✅ Mostrar avatares de desaprobadores
- ✅ Contar votos de desaprobación
- ✅ Calcular porcentaje de desaprobación vs aprobación
- ✅ Endpoint para obtener votos desglosados (aprobadores + desaprobadores)

---

## 🔄 Flujo de Votación

```
Usuario ve proposición
    ↓
┌─────────────────────────────────┐
│  Sección de Votos:              │
│                                 │
│  👍 7 Aprobaron                 │
│  [👤] [👤] [👤]                │
│                                 │
│  👎 3 Desaprobaron              │
│  [👤] [👤] [👤]                │
│                                 │
│  [Aprobar] [Desaprobar]         │
└─────────────────────────────────┘
    ↓
Usuario selecciona Aprobar o Desaprobar
    ↓
Se envía voto al servidor (approved: true/false)
    ↓
Se recalcula porcentaje
    ↓
Se actualiza UI con nuevos votos
```

---

## 🗄️ Base de Datos

### Tabla Existente: `pre_match_votes`
```sql
id, pre_match_proposition_id, user_id, approved (boolean), created_at, updated_at
```

**Estado:** ✅ Ya existe
**Cambios:** ❌ NO SE REQUIEREN

Los votos ya soportan:
- `approved = true` → Aprobación (👍)
- `approved = false` → Desaprobación (👎)

---

## 🔌 API Endpoints

### Existentes (Utilizar)
- `POST /api/pre-match-propositions/{id}/vote` - Crear/actualizar voto

**Request:**
```json
{
  "approved": true|false
}
```

**Response:**
```json
{
  "message": "Voto registrado",
  "approval_percentage": 75,
  "votes_data": {
    "approved": [
      { "id": 1, "name": "Juan", "avatar": "..." },
      { "id": 2, "name": "Pedro", "avatar": "..." }
    ],
    "rejected": [
      { "id": 3, "name": "Maria", "avatar": "..." }
    ]
  }
}
```

### Modificaciones Necesarias
- ✅ `voteProposition()` ya existe en `PreMatchController`
- ⚠️ **IMPORTANTE:** Retornar detalles de votos (usuarios + avatares)

---

## 👨‍💻 Cambios de Backend

### 1. Actualizar `PreMatchController@voteProposition()`

**Archivo:** `app/Http/Controllers/Api/PreMatchController.php`

**Cambios:**
```php
public function voteProposition(Request $request, $propositionId)
{
    // ... validación existente ...

    // Crear o actualizar voto
    PreMatchVote::updateOrCreate(
        ['pre_match_proposition_id' => $propositionId, 'user_id' => auth()->id()],
        ['approved' => $request->boolean('approved')]
    );

    // ✅ NUEVO: Obtener detalles de votos
    $approvalVotes = PreMatchVote::where('pre_match_proposition_id', $propositionId)
        ->where('approved', true)
        ->with('user:id,name,avatar')
        ->get();

    $rejectionVotes = PreMatchVote::where('pre_match_proposition_id', $propositionId)
        ->where('approved', false)
        ->with('user:id,name,avatar')
        ->get();

    // ✅ NUEVO: Calcular porcentajes
    $totalVotes = $approvalVotes->count() + $rejectionVotes->count();
    $approvalPercentage = $totalVotes > 0 ? ($approvalVotes->count() / $totalVotes) * 100 : 0;

    return response()->json([
        'message' => 'Voto registrado',
        'approval_percentage' => round($approvalPercentage),
        'total_votes' => $totalVotes,
        'approval_count' => $approvalVotes->count(),
        'rejection_count' => $rejectionVotes->count(),
        'approvers' => $approvalVotes->map(fn($v) => [
            'id' => $v->user->id,
            'name' => $v->user->name,
            'avatar' => $v->user->avatar
        ]),
        'rejectors' => $rejectionVotes->map(fn($v) => [
            'id' => $v->user->id,
            'name' => $v->user->name,
            'avatar' => $v->user->avatar
        ])
    ]);
}
```

### 2. Verificar Relaciones en Modelos

**Archivo:** `app/Models/PreMatchVote.php`

Asegurar que existe:
```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

---

## 🎨 Cambios de Frontend

### 1. Vista Principal: `pre-match/show.blade.php`

**Ubicación:** Sección de proposiciones

**Cambios:**
```blade
@foreach($preMatch->propositions as $proposition)
    <div class="proposition-card">
        <!-- Contenido existente -->
        
        <!-- ✅ NUEVA SECCIÓN: Votos Desglosados -->
        <div class="votes-section">
            <div class="votes-row">
                <!-- Aprobaciones -->
                <div class="votes-group">
                    <span class="vote-icon">👍</span>
                    <span class="vote-count">{{ $proposition->votes->where('approved', true)->count() }}</span>
                    <div class="avatars-container">
                        @foreach($proposition->votes->where('approved', true) as $vote)
                            <img src="{{ $vote->user->avatar }}" 
                                 alt="{{ $vote->user->name }}"
                                 title="{{ $vote->user->name }}"
                                 class="avatar-small">
                        @endforeach
                    </div>
                </div>

                <!-- Desaprobaciones -->
                <div class="votes-group">
                    <span class="vote-icon">👎</span>
                    <span class="vote-count">{{ $proposition->votes->where('approved', false)->count() }}</span>
                    <div class="avatars-container">
                        @foreach($proposition->votes->where('approved', false) as $vote)
                            <img src="{{ $vote->user->avatar }}" 
                                 alt="{{ $vote->user->name }}"
                                 title="{{ $vote->user->name }}"
                                 class="avatar-small">
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ✅ NUEVA: Botones de Votación -->
            <div class="voting-buttons">
                <button class="vote-btn approve-btn" onclick="voteProposition({{ $proposition->id }}, true)" title="Aprobar">
                    👍 Aprobar
                </button>
                <button class="vote-btn reject-btn" onclick="voteProposition({{ $proposition->id }}, false)" title="Rechazar">
                    👎 Rechazar
                </button>
            </div>
        </div>
    </div>
@endforeach
```

### 2. JavaScript: Actualizar función `voteProposition()`

**Ubicación:** Script en `pre-match/show.blade.php`

**Cambios:**
```javascript
async function voteProposition(propositionId, approved) {
    try {
        const response = await fetch(
            `/api/pre-match-propositions/${propositionId}/vote`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name="_csrf-token"]')?.value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ approved })
            }
        );

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();

        // ✅ NUEVO: Actualizar UI con datos del servidor
        updatePropositionVotes(propositionId, data);
        showToast('✓ Voto registrado', 'success', 2000);

    } catch (error) {
        console.error('Error votando:', error);
        showToast('Error al votar', 'error', 3000);
    }
}

function updatePropositionVotes(propositionId, data) {
    const card = document.querySelector(`[data-proposition-id="${propositionId}"]`);
    if (!card) return;

    // Renderizar nuevo componente de votos
    const html = `
        <div class="votes-row">
            <div class="votes-group">
                <span class="vote-icon">👍</span>
                <span class="vote-count">${data.approval_count}</span>
                <div class="avatars-container">
                    ${data.approvers.map(u => 
                        `<img src="${u.avatar}" alt="${u.name}" title="${u.name}" class="avatar-small">`
                    ).join('')}
                </div>
            </div>
            <div class="votes-group">
                <span class="vote-icon">👎</span>
                <span class="vote-count">${data.rejection_count}</span>
                <div class="avatars-container">
                    ${data.rejectors.map(u => 
                        `<img src="${u.avatar}" alt="${u.name}" title="${u.name}" class="avatar-small">`
                    ).join('')}
                </div>
            </div>
        </div>
    `;

    const votesSection = card.querySelector('.votes-section');
    if (votesSection) votesSection.innerHTML = html;
}
```

### 3. Estilos CSS

**Ubicación:** Variables de estilo existentes

```css
.votes-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 12px;
    background: rgba(0, 222, 176, 0.05);
    border-radius: 8px;
    margin-top: 8px;
}

.votes-row {
    display: flex;
    gap: 16px;
    justify-content: space-between;
}

.votes-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.vote-icon {
    font-size: 16px;
}

.vote-count {
    font-weight: 700;
    font-size: 12px;
    min-width: 20px;
}

.avatars-container {
    display: flex;
    gap: -8px;
}

.avatar-small {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid white;
    margin-left: -8px;
    first-child: margin-left: 0;
    cursor: pointer;
}

.voting-buttons {
    display: flex;
    gap: 8px;
}

.vote-btn {
    flex: 1;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.approve-btn {
    background: #4CAF50;
    color: white;
}

.approve-btn:hover {
    background: #45a049;
}

.reject-btn {
    background: #ff6b6b;
    color: white;
}

.reject-btn:hover {
    background: #ff5252;
}
```

---

## ✅ Checklist de Implementación

### Backend
- [ ] Actualizar `PreMatchController@voteProposition()` para retornar votos desglosados
- [ ] Asegurar que `PreMatchVote` tiene relación `user()`
- [ ] Probar endpoint con Postman/Thunder Client
- [ ] Verificar que `User.avatar` existe en base de datos

### Frontend  
- [ ] Agregar sección de votos en template de proposiciones
- [ ] Mostrar avatares de aprobadores
- [ ] Mostrar avatares de desaprobadores
- [ ] Implementar botones 👍 Aprobar y 👎 Rechazar
- [ ] Actualizar función `voteProposition()` para enviar `approved`
- [ ] Implementar `updatePropositionVotes()` para actualizar UI
- [ ] Agregar estilos CSS (avatares, botones, contenedor)

### Testing
- [ ] Probar votación local con avatares visibles
- [ ] Probar recuento de votos actualizado en tiempo real
- [ ] Probar hover/tooltips de avatares
- [ ] Validar que un usuario no puede votar 2 veces (updateOrCreate)
- [ ] En producción: verificar que avatares se cargan correctamente

---

## 📊 Porcentaje de Aprobación

**Fórmula:**
```
Porcentaje de Aprobación = (Votos Aprobación / Total Votos) * 100
```

**Ejemplo:**
- 7 aprobaciones
- 3 desaprobaciones
- **Porcentaje = (7/10) * 100 = 70%**

**Visualización propuesta:**
```
Aprobación: 70% ✅
██████░░░░ (barra de progreso opcional)
```

---

## 🔒 Validaciones

- ✅ Usuario autenticado puede votar
- ✅ Un usuario solo puede tener 1 voto por proposición (updateOrCreate)
- ✅ El voto puede cambiarse (boton se actualiza si ya votó)
- ✅ El avatar del usuario debe existir (fallback a default si no)
- ✅ Solo usuarios del grupo pueden votar

---

## 📝 Archivos a Modificar

1. **Backend:**
   - `app/Http/Controllers/Api/PreMatchController.php` - Método `voteProposition()`

2. **Frontend:**
   - `resources/views/pre-match/show.blade.php` - Template + JS

3. **Estilos:**
   - Mismo archivo o `public/css/pre-match.css` (si existe)

---

## 🚀 Orden de Ejecución

1. ✅ Backend: Actualizar endpoint para retornar datos de votos
2. ✅ Backend: Probar con Postman
3. ✅ Frontend: Agregar sección de votos HTML
4. ✅ Frontend: Agregar CSS
5. ✅ Frontend: Actualizar JavaScript
6. ✅ Testing local
7. ✅ Commit + Deploy

---

## 🎥 Resultado Esperado

**Estado Final en Pre Match Show:**

```
┌─────────────────────────────────────┐
│ Proposición: "Gol de chilena"       │
│ Por: Juan                            │
│                                      │
│ 👍 7  [👤👤👤👤👤👤👤]             │
│ 👎 3  [👤👤👤]                    │
│                                      │
│ [👍 Aprobar] [👎 Rechazar]          │
└─────────────────────────────────────┘
```

Cuando el usuario hace clic:
- UI se actualiza instantáneamente
- Avatar del usuario aparece en la sección correspondiente
- Si ya había votado, el avatar se mueve de una sección a la otra

---

## 📌 Notas Importantes

- **Datos existentes:** El modelo de votos ya soporta todo esto
- **No hay migrations nuevas:** Solo cambios de lógica de negocio
- **Compatibilidad:** El cambio es backward-compatible
- **Performance:** Considerar lazy-loading de avatares si muchas (> 50)
