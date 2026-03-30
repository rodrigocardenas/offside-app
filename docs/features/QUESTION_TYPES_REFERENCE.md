# 📋 Referencia Completa: Tipos de Preguntas Soportadas

**Última actualización:** Febrero 17, 2026  
**Status:** ✅ Completa - Proyecto en producción

---

## 📊 Tabla de Contenidos

1. [Preguntas Actualmente Soportadas](#preguntas-actualmente-soportadas) (14 tipos)
2. [Preguntas Sugeridas - Implementables](#preguntas-sugeridas---implementables) (12 tipos)
3. [Datos Disponibles](#datos-disponibles)
4. [Criterios de Implementación](#criterios-de-implementación)

---

## ✅ Preguntas Actualmente Soportadas

### 🟢 SOPORTE 100% COMPLETO (14 tipos)

#### 1. **RESULTADO DEL PARTIDO** 🏆
- **Tipo:** Score-based (No requiere eventos)
- **Palabras clave:** resultado, ganador, victoria, gana, ganará, quién gana, quién ganará
- **Datos utilizados:** `home_team_score`, `away_team_score`
- **Opciones típicas:**
  - Victoria Home / Victoria [nombre equipo]
  - Victoria Away / Victoria [nombre contrario]
  - Empate
- **Ejemplo:**
  ```
  ✅ "¿Cuál será el resultado del partido Arsenal vs Liverpool?"
  Opciones: Victoria Arsenal | Victoria Liverpool | Empate
  Partido: 2-1
  → Correcta: Victoria Arsenal
  ```
- **Precisión:** 100% (datos del partido)
- **Dependencias:** Ninguna (siempre disponible)

---

#### 2. **PRIMER GOL** ⚽
- **Tipo:** Event-based (Requiere eventos verificados)
- **Palabras clave:** primer gol, anotará primer, anotará el primer, first goal
- **Datos utilizados:** `events[type='GOAL']` (primer evento)
- **Opciones típicas:**
  - Home / [nombre equipo]
  - Away / [nombre equipo]
  - Ninguno
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo anotará el primer gol?"
  Opciones: Cremonese | Inter | Ninguno
  Eventos: [Goal at 16min by Inter, Goal at 31min by Inter]
  → Correcta: Inter
  ```
- **Precisión:** 100% (si hay eventos)
- **Dependencias:** Eventos verificados de API Football o Gemini

---

#### 3. **ÚLTIMO GOL** ⚽
- **Tipo:** Event-based
- **Palabras clave:** último gol, anotará el último, anotará último, last goal
- **Datos utilizados:** `events[type='GOAL']` (último evento)
- **Opciones:** Home, Away, Ninguno
- **Ejemplo:**
  ```
  ✅ Partido: 0-2
  Eventos: [Goal Inter 16min, Goal Inter 31min]
  → Correcta: Inter
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos verificados

---

#### 4. **FALTAS (FOULS)** 📋
- **Tipo:** Statistics-based
- **Palabras clave:** más faltas, faltas, fouls, falta cometida
- **Datos utilizados:** `statistics[home][fouls]`, `statistics[away][fouls]`
- **Opciones:** Home, Away, Same amount / Ninguno
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo recibirá más faltas?"
  Stats: Home 14 fouls, Away 10 fouls
  → Correcta: Home
  ```
- **Precisión:** 100%
- **Dependencias:** Estadísticas del partido

---

#### 5. **TARJETAS AMARILLAS** 🟨
- **Tipo:** Event-based
- **Palabras clave:** tarjetas amarillas, amarillas, yellow card, yellow cards
- **Datos utilizados:** `events[type='CARD' AND detail contains 'Yellow']`
- **Opciones:** Home, Away, Same amount / Ninguno
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo recibirá más tarjetas amarillas?"
  Eventos: Home 3 yellows, Away 2 yellows
  → Correcta: Home
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos verificados

---

#### 6. **TARJETAS ROJAS** 🔴
- **Tipo:** Event-based
- **Palabras clave:** tarjetas rojas, rojas, red card, red cards
- **Datos utilizados:** `events[type='CARD' AND detail contains 'Red']`
- **Opciones:** Home, Away, None
- **Ejemplo:**
  ```
  ✅ Partido: 1-1
  Eventos: Home 0 reds, Away 1 red
  → Correcta: Away
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos verificados

---

#### 7. **AUTOGOLES** 🔄
- **Tipo:** Event-based
- **Palabras clave:** autogol, auto gol, own goal, own goals
- **Datos utilizados:** `events[type='OWN_GOAL']`
- **Opciones:** Home, Away, None / ¿Habrá autogol? → Sí/No
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo anotará un autogol?"
  Eventos: [OWN_GOAL by Home team player]
  → Correcta: Home
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos verificados con detail field

---

#### 8. **GOLES DE PENAL** 🥅
- **Tipo:** Event-based
- **Palabras clave:** penal, penalty, gol de penal, penalty goal, desde los 12 pasos
- **Datos utilizados:** `events[type='GOAL' AND detail contains 'Penalty']`
- **Opciones:** Home, Away, ¿Habrá? → Sí/No
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo anotará un gol de penal?"
  Eventos: [Goal 45min Away detail: 'Penalty Goal']
  → Correcta: Away
  ```
- **Precisión:** 100% (si API Football proporciona detail)
- **Dependencias:** Eventos con campo `detail` verificado

---

#### 9. **GOLES DE TIRO LIBRE** 🎯
- **Tipo:** Event-based
- **Palabras clave:** tiro libre, free kick, gol directo, gol de tiro libre
- **Datos utilizados:** `events[type='GOAL' AND detail contains 'Free Kick']`
- **Opciones:** Home, Away, ¿Habrá? → Sí/No
- **Ejemplo:**
  ```
  ✅ "¿Habrá gol de tiro libre?"
  Eventos: [Goal 60min Home detail: 'Free Kick Goal']
  → Correcta: Sí / Home team
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos con campo `detail` verificado

---

#### 10. **GOLES DE CÓRNER** 🚩
- **Tipo:** Event-based
- **Palabras clave:** córner, corner, gol de córner, gol de corner
- **Datos utilizados:** `events[type='GOAL' AND detail contains 'Corner']`
- **Opciones:** Home, Away, ¿Habrá? → Sí/No
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo anotará un gol de córner?"
  Eventos: [Goal at 38min Away detail: 'Corner Goal']
  → Correcta: Away
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos con campo `detail` verificado

---

#### 11. **POSESIÓN DE BALÓN** 🎮
- **Tipo:** Statistics-based
- **Palabras clave:** posesión, possession, tendrá más posesión, ball possession
- **Datos utilizados:** `statistics[home][possession]`, `statistics[away][possession]`
- **Opciones:** Home, Away (+ % en algunos casos)
- **Ejemplo:**
  ```
  ✅ "¿Cuál equipo tendrá más posesión?"
  Stats: Home 65%, Away 35%
  → Correcta: Home
  ```
- **Precisión:** 100%
- **Dependencias:** Estadísticas del partido

---

#### 12. **AMBOS EQUIPOS ANOTAN** 👥
- **Tipo:** Score-based
- **Palabras clave:** ambos anotan, both score, los dos anotan, marcarán ambos
- **Datos utilizados:** `home_team_score > 0 AND away_team_score > 0`
- **Opciones:** Sí / No / Both teams score?
- **Ejemplo:**
  ```
  ✅ "¿Ambos equipos anotarán?"
  Resultado: 2-1
  → Correcta: Sí
  ```
- **Precisión:** 100%
- **Dependencias:** Ninguna (siempre disponible)

---

#### 13. **MARCADOR EXACTO** 🎯
- **Tipo:** Score-based
- **Palabras clave:** marcador exacto, exact score, score exacto, será el resultado
- **Datos utilizados:** `home_team_score - away_team_score`
- **Opciones:** 0-0, 1-0, 1-1, 2-0, 2-1, etc.
- **Ejemplo:**
  ```
  ✅ "¿Cuál será el marcador exacto?"
  Opciones: 1-1 | 2-0 | 2-1 | 3-1
  Resultado: 2-1
  → Correcta: 2-1
  ```
- **Precisión:** 100%
- **Dependencias:** Ninguna (siempre disponible)

---

#### 14. **GOLES OVER/UNDER** 📊
- **Tipo:** Score-based
- **Palabras clave:** más de, menos de, over, under, total goles, goles en el partido
- **Datos utilizados:** `home_team_score + away_team_score`
- **Opciones:** Over 1.5, Under 2.5, Over 2.5, etc.
- **Ejemplo:**
  ```
  ✅ "¿Habrá más de 2.5 goles?"
  Resultado: 2-1 (total 3 goles)
  → Correcta: Sí / Over 2.5
  ```
- **Precisión:** 100%
- **Dependencias:** Ninguna (siempre disponible)

---

#### 15. **GOL ANTES DE MINUTO X** ⏱️
- **Tipo:** Event-based
- **Palabras clave:** antes del minuto, antes de los [X] minutos, gol en primer tiempo, early goal
- **Datos utilizados:** `events[type='GOAL']` filtrado por minuto
- **Opciones:** Sí / No / Equipo
- **Ejemplo:**
  ```
  ✅ "¿Habrá gol antes de los 15 minutos?"
  Eventos: [Goal at 16min] - NO cuenta
  → Correcta: No
  
  ✅ "¿Habrá gol antes de los 20 minutos?"
  Eventos: [Goal at 16min] - SÍ cuenta
  → Correcta: Sí / Home
  ```
- **Precisión:** 100%
- **Dependencias:** Eventos verificados

---

## 🚀 Preguntas Sugeridas - Implementables

### Análisis de Datos Disponibles

```json
// Estructura de EVENTOS disponible
{
  "minute": 16,           // ✅ Disponible
  "type": "GOAL",         // ✅ Disponible
  "team": "Inter",        // ✅ Disponible
  "player": "L. Martinez",// ✅ Disponible
  "assist": "F. Dimarco", // ✅ Disponible
  "detail": "Normal Goal" // ✅ Disponible (API Football)
}

// Estructura de ESTADÍSTICAS disponible
{
  "home": {
    "fouls": 14,          // ✅ Disponible
    "possession": 65,     // ✅ Disponible
    "passes": 450,        // ✅ Disponible (en algunos casos)
    "shots": 12,          // ✅ Disponible (en algunos casos)
    "shots_on_target": 5, // ✅ Disponible
    "corners": 8,         // ✅ Disponible
    "free_kicks": 15,     // ✅ Disponible (en algunos casos)
    "offsides": 3,        // ✅ Disponible (en algunos casos)
    "passes_accuracy": 78 // ✅ Disponible (en algunos casos)
  },
  "away": { /* mismo */ }
}
```

### 🟡 SUGERIDAS - ALTA PRIORIDAD (6 tipos)

#### S1. **GOL A FAVOR (Sí/No - Último 15 min)** ⏰
**Descripción:** ¿Se anotará un gol en los últimos 15 minutos del partido?
- **Datos necesarios:** `events[type='GOAL' AND minute >= 75]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐⭐ Alto
- **Caso de uso:** Preguntas dramaticidad / final de partido
- **Implementación:**
  ```php
  private function evaluateLateGoal(Question $q, FootballMatch $m): array {
      $lateGoals = array_filter($events, fn($e) => 
          $e['type'] === 'GOAL' && $e['minute'] >= 75
      );
      return empty($lateGoals) ? [noOption] : [yesOption];
  }
  ```

---

#### S2. **TIROS AL ARCO (SHOTS ON TARGET)** 🎯
**Descripción:** ¿Cuál equipo tuvo más tiros al arco?
- **Datos necesarios:** `statistics[home/away][shots_on_target]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐ Medio
- **Caso de uso:** Predicciones ofensivas vs defensivas
- **Implementación:**
  ```php
  private function evaluateShotsOnTarget(Question $q, FootballMatch $m): array {
      $homeShots = stats['home']['shots_on_target'] ?? 0;
      $awayShots = stats['away']['shots_on_target'] ?? 0;
      return compareTeamStats($homeShots, $awayShots);
  }
  ```

---

#### S3. **TOTAL TIROS (SHOTS)** 🔫
**Descripción:** ¿Cuál equipo realizó más tiros (al arco o no)?
- **Datos necesarios:** `statistics[home/away][shots]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐ Medio
- **Caso de uso:** Predicciones sobre intensidad de ataque
- **Implementación:** Similar a S2

---

#### S4. **CÓRNERS (CORNERS)** 🏳️
**Descripción:** ¿Cuál equipo obtuvo más córners?
- **Datos necesarios:** `statistics[home/away][corners]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐ Medio
- **Caso de uso:** Predicciones sobre dominio territorial
- **Implementación:** Similar a S2

---

#### S5. **PRIMER GOLO EN TIEMPO (EARLY GOAL - MINUTO 45)** ⏱️
**Descripción:** ¿Se anotará al menos un gol antes del descanso?
- **Datos necesarios:** `events[type='GOAL' AND minute <= 45]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐⭐ Alto
- **Caso de uso:** Preguntas sobre ritmo del primer tiempo
- **Implementación:**
  ```php
  private function evaluateGoalBeforeHalftime(Question $q, FootballMatch $m): array {
      return $this->evaluateGoalBeforeMinute($q, $m, 45);
  }
  ```

---

#### S6. **GOLES DESPUÉS DEL MINUTO 60** 🎬
**Descripción:** ¿Se anotará al menos un gol después del minuto 60?
- **Datos necesarios:** `events[type='GOAL' AND minute > 60]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐⭐ Medio
- **Caso de uso:** Cambios tácticos / cansancio
- **Implementación:** Similar a S1

---

### 🟡 SUGERIDAS - MEDIA PRIORIDAD (4 tipos)

#### S7. **OFFSIDES COUNT** 📍
**Descripción:** ¿Cuál equipo tiene más offsides?
- **Datos necesarios:** `statistics[home/away][offsides]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐ Bajo-Medio
- **Disponibilidad:** 60-70% de partidos (API Football PRO)
- **Caso de uso:** Análisis defensivo

---

#### S8. **PRECISIÓN DE PASES (PASS ACCURACY)** 📊
**Descripción:** ¿Cuál equipo tiene mayor precisión de pases?
- **Datos necesarios:** `statistics[home/away][passes_accuracy]`
- **Dificultad:** ⭐ Trivial
- **Valor para usuarios:** ⭐⭐ Bajo-Medio
- **Disponibilidad:** 70% de partidos
- **Caso de uso:** Predicciones técnicas

---

#### S9. **TARJETAS TOTALES** 🎴
**Descripción:** ¿Cuál equipo recibirá más tarjetas (amarillas + rojas)?
- **Datos necesarios:** `count(events[type='CARD'])`
- **Dificultad:** ⭐ Fácil
- **Valor para usuarios:** ⭐⭐⭐ Medio
- **Caso de uso:** Predicciones disciplinarias
- **Implementación:**
  ```php
  private function evaluateTotalCards(Question $q, FootballMatch $m): array {
      $homeCards = count(array_filter($events, fn($e) => 
          $e['type'] === 'CARD' && $e['team'] === $m->home_team
      ));
      $awayCards = count(array_filter($events, fn($e) => 
          $e['type'] === 'CARD' && $e['team'] === $m->away_team
      ));
      return compareTeamStats($homeCards, $awayCards);
  }
  ```

---

#### S10. **RESULTADO EXACTO + MARCADOR GOLEADOR** 🎯
**Descripción:** "¿Quién anotará el gol decisivo?" o "¿Quién anotará en la victoria?"
- **Datos necesarios:** `events[type='GOAL']` + `match outcome`
- **Dificultad:** ⭐⭐ Medio
- **Valor para usuarios:** ⭐⭐⭐⭐ Alto
- **Caso de uso:** Predicciones muy específicas
- **Notas:** Requiere lista de jugadores en opciones

---

### 🟠 SUGERIDAS - BAJA PRIORIDAD (2 tipos)

#### S11. **PRIMER GOLEADOR EXACTO** 👤
**Descripción:** "¿Quién anotará el primer gol?" (nombre del jugador)
- **Datos necesarios:** `events[type='GOAL'][0]['player']`
- **Dificultad:** ⭐⭐ Medio-Difícil
- **Valor para usuarios:** ⭐⭐⭐⭐⭐ Muy Alto
- **Desafío:** Matching de nombres (Łukasz Martinez vs L. Martinez)
- **Caso de uso:** "Top scorer" / fantasy football
- **Notas:** Requiere fuzzy matching avanzado

---

#### S12. **ASISTENCIAS** 🤝
**Descripción:** "¿Cuál equipo hará más asistencias?"
- **Datos necesarios:** `events[].assist` (si está disponible)
- **Dificultad:** ⭐⭐ Medio
- **Valor para usuarios:** ⭐⭐ Bajo-Medio
- **Caso de uso:** Predicciones sobre juego colectivo
- **Notas:** Disponibilidad inconsistente en API Football

---

---

## 📊 Datos Disponibles

### Por Tipo de Fuente

#### ✅ SIEMPRE DISPONIBLES (Score-based)
```
match.home_team_score
match.away_team_score
match.status
match.date
match.home_team
match.away_team
```

#### ✅ CASI SIEMPRE (Events)
```
events[].type         // GOAL, CARD, SUBST
events[].team         // Nombre del equipo
events[].minute       // Minuto del evento
events[].player       // Nombre del jugador
events[].detail       // "Yellow Card", "Penalty Goal", etc. (API Football)
events[].assist       // Asistidor (API Football PRO)
statistics.home.fouls
statistics.away.fouls
statistics.home.possession
statistics.away.possession
```

#### ⚠️ A VECES (Depends on API Plan)
```
statistics.home.shots
statistics.away.shots
statistics.home.shots_on_target
statistics.away.shots_on_target
statistics.home.corners
statistics.away.corners
statistics.home.offsides  // 60-70%
statistics.away.offsides
statistics.home.passes    // 70%
statistics.away.passes
statistics.home.passes_accuracy  // 70%
statistics.away.passes_accuracy
```

#### ❌ RARO O NO DISPONIBLE
```
statistics.home.tackles
statistics.away.tackles
statistics.home.interceptions
statistics.away.interceptions
player.name_standardized  // Inconsistente
events[].location_x, location_y  // No de API Football
```

---

## 🎯 Criterios de Implementación

### Para agregar un nuevo tipo de pregunta, debe cumplir:

| Criterio | Descripción | Impacto |
|----------|-------------|-----------------|
| **Disponibilidad de datos** | ✅ Los datos deben estar disponibles en >80% de partidos | Alto - Evita "sin verificar" |
| **Precisión determinística** | ✅ Lógica 100% predecible (no depende de IA) | Alto - Consistencia |
| **Simplicidad de matching** | ✅ Identificación por keywords (máx. 5 palabras) | Medio - UX |
| **Valor para usuarios** | ✅ Las predicciones deben ser interesantes | Alto - Engagement |
| **Complejidad de implementación** | ✅ <100 líneas de código | Bajo - Mantenibilidad |
| **No requiere IA/Gemini** | ✅ Puramente determinístico | Alto - Costos/Velocidad |

---

## 🔧 Roadmap Sugerido

### FASE 1 (2-3 días) - HIGH PRIORITY
- [x] S1: Late Goal (Gol últimos 15 min)
- [x] S2: Shots on Target
- [x] S5: Goal before Halftime

### FASE 2 (1-2 días) - MEDIUM PRIORITY
- [ ] S3: Total Shots
- [ ] S4: Corners
- [ ] S9: Total Cards

### FASE 3 (Backlog) - LOW PRIORITY
- [ ] S6: Goals after minute 60
- [ ] S7: Offsides count
- [ ] S11: Primer goleador exacto (POC)

---

## 📝 Notas de Implementación

### Patrón de Código Recomendado

```php
/**
 * TIPO: DESCRIPCIÓN
 * 
 * Ejemplo: "¿Descripción?"
 * Opciones: Opción1, Opción2
 * Requisitos: events, statistics
 */
private function evaluateNewQuestionType(Question $q, FootballMatch $m): array {
    $correctOptionIds = [];
    
    // Obtener datos necesarios
    $events = $this->parseEvents($m->events ?? []);
    $statistics = $this->parseStatistics($m->statistics ?? []);
    
    // Lógica principal
    // ...
    
    // Buscar opciones correctas
    foreach ($q->options as $option) {
        if (/* condición */) {
            $correctOptionIds[] = $option->id;
        }
    }
    
    return $correctOptionIds;
}
```

### Incluir Nuevos Métodos en `evaluateQuestion()`

```php
elseif ($this->isQuestionAbout($questionText, 'keywords')) {
    $questionHandled = true;
    $correctOptions = $this->evaluateNewQuestionType($question, $match);
}
```

---

## 📞 Contacto y Soporte

Para sugerencias de nuevos tipos de preguntas:
1. Verificar en esta documentación si ya está soportado
2. Chequear en "Sugeridas" si está en roadmap
3. Evaluar criterios de implementación
4. Abrir issue/PR con propuesta

