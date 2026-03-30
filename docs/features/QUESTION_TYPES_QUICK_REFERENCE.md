# 📋 RESUMEN RÁPIDO: Tipos de Preguntas

## ✅ ACTUALMENTE SOPORTADAS (15 tipos)

| # | Tipo | Palabras Clave | Datos | Opciones | ⭐ |
|---|------|---|---|---|---|
| 1 | 🏆 Resultado | resultado, ganador, victoria | score | Home / Away / Empate | 5 |
| 2 | ⚽ Primer gol | primer gol, anotará primer | events | Home / Away / Ninguno | 5 |
| 3 | ⚽ Último gol | último gol, anotará último | events | Home / Away / Ninguno | 5 |
| 4 | 📋 Faltas | más faltas, faltas | stats | Home / Away / Igual | 4 |
| 5 | 🟨 Amarillas | tarjetas amarillas, amarillas | events | Home / Away / Igual | 4 |
| 6 | 🔴 Rojas | tarjetas rojas, rojas | events | Home / Away / Ninguno | 3 |
| 7 | 🔄 Autogol | autogol, auto gol | events | Home / Away / Ninguno | 3 |
| 8 | 🥅 Penal | penal, penalty | events | Home / Away / ¿Habrá? | 4 |
| 9 | 🎯 Tiro libre | tiro libre, free kick | events | Home / Away / ¿Habrá? | 4 |
| 10 | 🚩 Córner | córner, corner | events | Home / Away / ¿Habrá? | 4 |
| 11 | 🎮 Posesión | posesión, possession | stats | Home / Away | 5 |
| 12 | 👥 Ambos anotan | ambos anotan, both score | score | Sí / No | 5 |
| 13 | 🎯 Score exacto | marcador exacto, score exacto | score | 1-1, 2-0, 2-1... | 4 |
| 14 | 📊 Over/Under | más de, menos de, over, under | score | Over X / Under Y | 5 |
| 15 | ⏱️ Gol antes min X | antes del minuto, gol temprano | events | Sí / No / Home / Away | 5 |

**Leyenda:** ⭐ = Value for users (1=low, 5=high)

---

## 🚀 SUGERIDAS - IMPLEMENTABLES (12 tipos)

| # | Tipo | Descripción | Datos | Dificultad | Prioridad | Valor |
|---|------|---|---|---|---|---|
| S1 | ⏰ Late Goal | Gol en últimos 15 min | events | ⭐ | 🔴 High | ⭐⭐⭐⭐ |
| S2 | 🎯 Shots Target | Tiros al arco | stats | ⭐ | 🔴 High | ⭐⭐⭐ |
| S3 | 🔫 Total Shots | Total de tiros | stats | ⭐ | 🟡 Medium | ⭐⭐⭐ |
| S4 | 🏳️ Corners | Total córners | stats | ⭐ | 🟡 Medium | ⭐⭐⭐ |
| S5 | 🎬 1st Half Goal | Gol antes min 45 | events | ⭐ | 🔴 High | ⭐⭐⭐⭐ |
| S6 | 🎭 2nd Half Goal | Gol después min 60 | events | ⭐ | 🟡 Medium | ⭐⭐⭐ |
| S7 | 📍 Offsides | Total offsides | stats | ⭐ | 🟠 Low | ⭐⭐ |
| S8 | 📊 Pass Accuracy | Precisión de pases | stats | ⭐ | 🟠 Low | ⭐⭐ |
| S9 | 🎴 Total Cards | Tarjetas totales | events | ⭐⭐ | 🟡 Medium | ⭐⭐⭐ |
| S10 | ⚡ Goleador decisivo | Quién marca el gol ganador | events | ⭐⭐ | 🟡 Medium | ⭐⭐⭐⭐ |
| S11 | 👤 Primer goleador | Quién anota primero (jugador) | events | ⭐⭐⭐ | 🟠 Low | ⭐⭐⭐⭐⭐ |
| S12 | 🤝 Asistencias | Cuál equipo asiste más | events | ⭐⭐ | 🟠 Low | ⭐⭐ |

---

## 🎯 Guía Rápida de Implementación

### Para S1-S6 (Trivial - 1 hora c/u)
```php
// Patrón simple: filtrar + comparar
private function evaluateNewType(Question $q, FootballMatch $m): array {
    $events = $this->parseEvents($m->events ?? []);
    $filtered = array_filter($events, fn($e) => /* condition */);
    return $this->findMatchingOptions($q, /* result */);
}
```

### Para S7-S9 (Fácil - 2 horas c/u)
```php
// Patrón: contar eventos + comparar equipos
$homeCount = count(array_filter($events, fn($e) => 
    $e['team'] === $m->home_team && /* condition */
));
```

### Para S10-S12 (Medio - 4+ horas c/u)
```php
// Patrón: fuzzy matching + jugadores
// Requiere lógica de names matching y fallbacks
```

---

## 📊 Status por Fuente de Datos

### ✅ Siempre disponible (Score-based)
- Tipos 1, 12, 13, 14
- S1 parcial, S5, S6 parcial

### ✅ Généralmente disponible (Events/Stats)
- Tipos 2-11, 15
- S2-S4, S9

### ⚠️ Disponibilidad variable (API Football PRO)
- S7, S8, S10, S12

### ❌ No soportado actualmente
- Tackles, Interceptions, Player names exactos
- Localización de eventos (x, y)

---

## 🔗 Referencias Completas

- **Documentación detallada:** [QUESTION_TYPES_REFERENCE.md](QUESTION_TYPES_REFERENCE.md)
- **Implementación actual:** `app/Services/QuestionEvaluationService.php`
- **Histórico:** [PHASE_2_COMPLETED.md](archived/PHASE_2_COMPLETED.md)
- **Código de tests:** `tests/Unit/Services/FirstGoalQuestionEvaluationTest.php`

