## 🏗️ ARQUITECTURA: Pipeline de Actualización con API Football

```
CADA HORA (Scheduler Laravel)
         ↓
   UpdateFinishedMatchesJob (:00)
         │
         ├─ Busca partidos sin actualizar (últimas 72h)
         │  └─ WHERE status NOT IN ['FINISHED', 'Match Finished']
         │  └─ AND date BETWEEN -72h y -2h
         │
         ├─ Divide en LOTES de 5 partidos
         │  └─ Lote 1: delay 10s
         │  └─ Lote 2: delay 20s
         │  └─ Lote 3: delay 30s
         │  └─ ...etc
         │
         └─ Despacha TODOS a ProcessMatchBatchJob
                ↓ (cada lote con su delay en queue)
            QUEUE WORKER
                ↓
         ProcessMatchBatchJob (Ejecuta Lote 1)
            ↓ PARA CADA PARTIDO
            │
            ├─→ [INTENTO 1] API Football (PRIORITARIO)
            │   ├─ Usa footballService->updateMatchFromApi()
            │   ├─ ✅ SI ÉXITO: Actualiza, continúa
            │   └─ ❌ SI FALLA: Intenta siguiente
            │
            ├─→ [INTENTO 2] Gemini + Web Search (FALLBACK)
            │   ├─ Usa geminiService->getMatchResult()
            │   ├─ Con grounding = true (búsqueda en web)
            │   ├─ ✅ SI OBTIENE SCORE: Actualiza
            │   └─ ❌ SI FALLA: NO ACTUALIZA
            │
            └─→ [POLÍTICA] Verificada-Only
                ❌ Si ambas fallan = NO ACTUALIZAR
                   (Mejor esperar datos reales que datos falsos)

5 MINUTOS DESPUÉS (:05)
         ↓
   VerifyFinishedMatchesHourlyJob
         │
         ├─ Busca preguntas SIN verificar
         │  └─ WHERE result_verified_at IS NULL
         │
         ├─ SOLO EN PARTIDOS CON STATUS = 'Match Finished'
         │  └─ (Requiere que UpdateFinishedMatchesJob haya actualizado)
         │
         └─ Despacha VerifyAllQuestionsJob
                ↓
            VERIFICA respuestas de usuarios
            BASADA EN scores actualizados
```

---

## 🔌 INTEGRACIÓN: API Football + Gemini

### Flujo de Datos de API Football

```php
// 1. Obtener partido de base de datos
$match = FootballMatch::find($matchId);
// → Tiene: external_id, home_team, away_team, date, league

// 2. Servicio busca en API Football
$fixture = $footballService->obtenerFixtureDirecto($fixtureId);
// → API: https://api-football-v1.p.rapidapi.com/fixtures?id=123456

// 3. Extrae datos
if ($fixture['fixture']['status']['short'] === 'FT') {  // FT = Final Time
    $homeScore = $fixture['goals']['home'];
    $awayScore = $fixture['goals']['away'];
    // → Retorna: ['home_score' => 2, 'away_score' => 1]
}

// 4. Actualiza en BD
$match->update([
    'status' => 'Match Finished',
    'home_team_score' => $homeScore,
    'away_team_score' => $awayScore,
    'score' => '2 - 1'
]);
```

### Fallback: Gemini con Grounding

```php
// Si API Football falla, intenta Gemini
$result = $geminiService->getMatchResult(
    $match->home_team,
    $match->away_team,
    $match->date,
    $match->league,
    $forceRefresh = false,
    $useGrounding = true  // ← CRITICAL: Web search habilitada
);

// Gemini busca en Internet:
// "¿Cuál fue el resultado de Barcelona vs Madrid el 22 enero 2026?"
// → Retorna: ['home_score' => 2, 'away_score' => 1]
```

---

## 📊 ESTADO ACTUAL

| Componente | Estado | Detalles |
|-----------|--------|---------|
| **API Football** | ⏳ Requiere pago | Plan Premium $9.99/mes en RapidAPI |
| **ProcessMatchBatchJob** | ✅ Listo | Intenta API primero, luego Gemini |
| **UpdateFinishedMatchesJob** | ✅ Listo | Scheduler cada hora a :00 |
| **VerifyFinishedMatchesHourlyJob** | ✅ Listo | Verifica a :05 |
| **Gemini Grounding** | ⏳ Rate limited | 20/20 solicitudes/día gastadas |

---

## ✅ CHECKLIST: Paso a Paso

- [ ] **1. Suscribirse a API Football** (https://rapidapi.com/api-sports/api/api-football)
- [ ] **2. Copiar API Key** de RapidAPI
- [ ] **3. Actualizar `.env`**: `FOOTBALL_API_KEY=tu_key`
- [ ] **4. Reiniciar queue**: `php artisan queue:restart`
- [ ] **5. Verificar**: `php artisan matches:update --hours=72 --limit=5`
- [ ] **6. Esperar a que pase hora completa** para ver scheduler automático

---

## 🎯 Próximos Pasos

### HOY
- [ ] Revisar esta arquitectura
- [ ] Decidir: ¿Suscribirse a API Football o usar otra solución?

### MAÑANA (cuando API Football esté activo)
```bash
# El scheduler automático ejecutará esto cada hora:
UpdateFinishedMatchesJob → ProcessMatchBatchJob → Scores actualizados → VerifyFinishedMatchesHourlyJob

# Resultado esperado:
✅ Partidos con status "Match Finished"
✅ Scores correctos desde API Football
✅ Preguntas verificadas automáticamente
```

---

## 💡 Puntos Clave

1. **API Football es prioritario** - Se intenta primero
2. **Gemini es fallback** - Solo si API falla
3. **Política verificada-only** - Sin datos falsos
4. **Queue-based** - No bloquea el servidor
5. **Exponential backoff** - Respeta rate limits automáticamente

¿Necesitas ayuda con la suscripción a API Football? 🚀
