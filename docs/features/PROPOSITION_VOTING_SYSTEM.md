# Sistema de Votación de Desaprobación - Pre Match

## 📋 P>lanificación de Tarea

**Objetivo:** Agregar botón pequeño de desaprobación (👎) al lado del sistema de aprobación existente (sin modificar)

---

## 🎯 Requisitos Funcionales

### Actual (Estado Previo)
- ✅ Votos de aprobación (👍) - **NO TOCAR**
- ✅ Contador de aprobaciones - **NO TOCAR**
- ✅ Botones de aprobación - **NO TOCAR**
- ❌ Botón de desaprobación (👎)
- ❌ Contador de desaprobaciones

### Nuevo (A Implementar)
- ✅ Botón 👎 pequeño al lado del botón 👍
- ✅ Contar votos de desaprobación
- ✅ Endpoint modificado para soportar desaprobación
- ✅ Actualizar UI con contador de desaprobados

---

## 🔄 Flujo de Votación (Cambio Mínimo)

```
Estado Actual:
┌──────────────────────┐
│ Propuesta: Gol       │
│ Aprobaciones: 7      │
│ [👍 Aprobar]         │
└──────────────────────┘

NUEVO - Solo agrega:
┌──────────────────────┐
│ Propuesta: Gol       │
│ Aprobaciones: 7      │
│ [👍 Aprobar] [👎]    │  ← Botón pequeño nuevo
│ Desaprobaciones: 2   │  ← Contador nuevo
└──────────────────────┘
```

---

## 🗄️ Base de Datos

### Tabla Existente: `pre_match_votes`
```sql
id, pre_match_proposition_id, user_id, approved (boolean), created_at, updated_at
```

**Estado:** ✅ Ya existe y soporta lo que necesitamos
- `approved = true` → Aprobación (👍) - ya implementado
- `approved = false` → Desaprobación (👎) - nuevo

---

## 🔌 API Endpoints

### Existentes (Reutilizar)
- `POST /api/pre-match-propositions/{id}/vote` - Ya existe

**Request:**
```json
{
  "approved": true|false
}
```

**Response (Modificar para retornar ambos contadores):**
```json
{
  "message": "Voto registrado",
  "approval_count": 7,
  "rejection_count": 2,
  "approval_percentage": 78
}
```

### Modificaciones Necesarias
- ✅ `voteProposition()` ya existe
- ⚠️ **CAMBIO MÍNIMO:** Retornar también `rejection_count`

---

## 👨‍💻 Cambios de Backend

### 1. Actualizar `PreMatchController@voteProposition()`

**Archivo:** `app/Http/Controllers/Api/PreMatchController.php`

**Cambios (MÍNIMOS):**
```php
public function voteProposition(Request $request, $propositionId)
{
    // ... código existente (sin cambios) ...

    // ✅ NUEVO: Retornar también recuento de desaprobaciones
    $rejectionCount = PreMatchVote::where('pre_match_proposition_id', $propositionId)
        ->where('approved', false)
        ->count();

    return response()->json([
        'message' => 'Voto registrado',
        'approval_count' => $approvalCount,        // Existente
        'rejection_count' => $rejectionCount,      // NUEVO
        'approval_percentage' => $approvalPercentage // Existente
    ]);
}
```

---

## 🎨 Cambios de Frontend

### 1. Vista: `pre-match/show.blade.php`

**Ubicación:** Al lado del contador de aprobaciones (sin tocar código existente)

**Cambio Visual Actual:**
```blade
<p style="...">
    ✅ Aprobadas: <span>{{ $preMatch->propositions->where('validation_status', 'approved')->count() }}</span>
</p>
<button onclick="voteProposition(...)">Aprobar</button>
```

**Cambio Visual Nuevo:**
```blade
<p style="...">
    ✅ Aprobadas: <span>{{ $preMatch->propositions->where('validation_status', 'approved')->count() }}</span>
    
    <!-- ✅ NUEVO: Botón pequeño de desaprobación al lado -->
    <button 
        onclick="voteProposition(..., false)" 
        style="padding: 4px 8px; font-size: 12px; margin-left: 6px; background: #ff6b6b; color: white; border: none; border-radius: 4px; cursor: pointer;"
        title="Rechazar esta propuesta">
        👎
    </button>
</p>

<!-- ✅ NUEVO: Mostrar contador de desaprobaciones (si existen) -->
@if($proposition->votes->where('approved', false)->count() > 0)
    <p style="font-size: 12px; color: #ff6b6b; margin-top: 4px;">
        👎 {{ $proposition->votes->where('approved', false)->count() }} desaprobaron
    </p>
@endif

<button onclick="voteProposition(...)">Aprobar</button>
```

### 2. JavaScript: Actualizar `voteProposition()`

**Sin cambios mayores** - La función ya maneja `approved` como parámetro:

```javascript
async function voteProposition(propositionId, approved = true) {  // ← Nuevo parámetro con default
    try {
        const response = await fetch(
            `/api/pre-match-propositions/${propositionId}/vote`,
            {
                method: 'POST',
                headers: { ... },
                body: JSON.stringify({ approved })  // ← Ahora puede ser true o false
            }
        );

        // Actualizar solo el contador si es necesario
        const data = await response.json();
        document.querySelector(`[data-proposition-${propositionId}-rejects]`).textContent = data.rejection_count;
        
    } catch (error) { ... }
}
```

---

## ✅ Checklist de Implementación

### Backend (5 minutos)
- [ ] Agregar `rejection_count` a respuesta de `voteProposition()`
- [ ] Probar endpoint con Postman

### Frontend (10 minutos)
- [ ] Agregar botón 👎 pequeño al lado del contador de aprobaciones
- [ ] Agregar texto "X desaprobaron" si hay votos negativos
- [ ] Actualizar `voteProposition()` para pasar `false` si es necesario
- [ ] Actualizar contador de desaprobaciones en UI tras votar

### Testing (5 minutos)
- [ ] Probar click en botón 👎
- [ ] Verificar que contador se actualiza
- [ ] Verificar que sigue funcionando el 👍

---

## 🎨 Estilos CSS (MINIMAL)

```css
/* Botón pequeño de desaprobación */
.reject-btn-small {
    padding: 4px 8px;
    font-size: 12px;
    margin-left: 6px;
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.reject-btn-small:hover {
    background: #ff5252;
}

.reject-text {
    font-size: 12px;
    color: #ff6b6b;
    margin-top: 4px;
}
```

---

## 🚀 Orden de Ejecución

1. ✅ Backend: Agregar `rejection_count` al endpoint
2. ✅ Backend: Verificar que endpoint retorna ambos valores
3. ✅ Frontend: Agregar botón 👎 en HTML
4. ✅ Frontend: Agregar texto de desaprobaciones
5. ✅ Testing local
6. ✅ Commit + Deploy

---

## 🎥 Resultado Esperado

**Visual Final (MÍNIMo, discreto):**

```
✅ Aprobadas: 7 [👎]
👎 2 desaprobaron

[👍 Aprobar]
```

**Comportamiento:**
- Click en [👎] → Registra desaprobación
- Contador se actualiza
- Si cambias de opinión, puedes votar de nuevo

---

## 📝 Archivos a Modificar

1. **Backend:**
   - `app/Http/Controllers/Api/PreMatchController.php` - Método `voteProposition()` (2-3 líneas)

2. **Frontend:**
   - `resources/views/pre-match/show.blade.php` - Template (3-5 líneas nuevas)
   - Mismo archivo - JavaScript (1-2 líneas)

3. **Estilos:**
   - Inline styles (no crear archivo nuevo)

---

## 📌 Notas Importantes

- ✅ **NO toca la aprobación actual** - Solo suma el botón 👎
- ✅ **Cambio backwards-compatible** - No rompe nada existente
- ✅ **Datos ya existen** - La BD ya soporta `approved: false`
- ✅ **Mínimo código** - Solo unas pocas líneas
- ✅ **Sin migraciones** - Cero cambios de schema

