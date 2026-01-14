Propuesta de Arquitectura Mejorada
==================================

PROBLEMA ACTUAL:
✅ Se obtienen datos del partido (score, eventos si Gemini responde bien)
❌ No se verifican preguntas correctamente porque:
   1. Gemini a veces retorna solo texto, no JSON con eventos
   2. Incluso si retorna eventos, se guarda como texto, no como JSON
   3. VerifyQuestionResultsJob se ejecuta con delay (2 min), puede fallar si datos no están listos

SOLUCIÓN PROPUESTA:
==================

CAMBIO 1: Separar Jobs en 3 fases bien definidas
────────────────────────────────────────────────

Phase 1: OBTENER RESULTADOS (ProcessMatchBatchJob)
   ├─ Intentar API Football
   ├─ Si falla, intentar Gemini BÁSICO (solo score, sin eventos)
   ├─ Si ambas fallan, marcar como NO_ENCONTRADO
   └─ Guardar datos con flag: has_detailed_events = false

Phase 2: INTENTAR OBTENER DETALLES (ExtractMatchDetailsJob - NUEVO)
   ├─ Para partidos SIN eventos JSON aún
   ├─ Llamar getDetailedMatchData() de Gemini
   ├─ Si obtiene eventos → Actualizar partido con eventos JSON
   ├─ Si no obtiene eventos → Dejar como está
   └─ Delay: 5 minutos (dar tiempo a datos de estar disponibles)

Phase 3: VERIFICAR PREGUNTAS (VerifyQuestionResultsJob)
   ├─ Esperar a Phase 2 (o ejecutarse 10 min después de match finished)
   ├─ Evaluar todas las preguntas del partido
   ├─ Si tiene eventos JSON → Verificar evento-based questions
   ├─ Si no tiene eventos JSON → Solo verificar score-based questions
   └─ Marcar pregunta como result_verified_at = now()


CAMBIO 2: Mejorar ProcessMatchBatchJob
──────────────────────────────────────

Actual:
   try getDetailedMatchData()
   if not exists → fallback a texto "Partido actualizado desde Gemini"

Mejorado:
   try getMatchResult() SOLO → guardar score
   → Defer a ExtractMatchDetailsJob para intentar JSON con eventos


CAMBIO 3: Crear ExtractMatchDetailsJob (NUEVO)
───────────────────────────────────────────────

Responsabilidades:
   ├─ Buscar partidos con status FINISHED pero sin eventos JSON
   ├─ Para cada uno, llamar getDetailedMatchData()
   ├─ Si obtiene datos válidos con eventos:
   │  ├─ Actualizar events = json_encode($events)
   │  ├─ Actualizar statistics.has_detailed_events = true
   │  └─ Log: "✅ Detalles extraídos para match X: N eventos"
   ├─ Si no obtiene:
   │  └─ Log: "⚠️ No se pudieron obtener detalles para match X"
   └─ Ejecución: Cada 5 minutos, máx 50 partidos por ejecución


CAMBIO 4: Mejorar VerifyQuestionResultsJob
───────────────────────────────────────────

Actual:
   Busca questions con result_verified_at = null
   Evalúa todas (algunas fallan porque no tienen eventos)

Mejorado:
   Igual lógica, pero QuestionEvaluationService:
   ├─ Si tiene eventos JSON → Verifica evento-based
   ├─ Si no tiene eventos JSON → Verifica SOLO score-based
   └─ Nunca retorna NULL, siempre retorna array (posiblemente vacío)


FLUJO DE EJECUCIÓN:
===================

1. ProcessRecentlyFinishedMatchesJob (Coordinador)
   ├─ +0s   : UpdateFinishedMatchesJob (obtiene partidos finalizados)
   │         └─ Despacha ProcessMatchBatchJob (por lotes)
   │
   ├─ +5s   : ProcessMatchBatchJob (múltiples ejecuciones)
   │         ├─ Try API Football
   │         ├─ Try Gemini getMatchResult() SOLO
   │         └─ Guardar score (sin intentar getDetailedMatchData())
   │            ✅ Resultado: Match guardado con score
   │
   ├─ +10s  : ExtractMatchDetailsJob (NUEVO - Despacha aquí)
   │         ├─ Buscar matches sin eventos JSON
   │         ├─ Para cada uno: try getDetailedMatchData()
   │         └─ Si obtiene: Actualizar con events JSON
   │            ✅ Resultado: Match tiene events si Gemini coopera
   │
   └─ +5min : VerifyQuestionResultsJob (espera suficiente)
            ├─ Para cada pregunta sin verificar
            ├─ Evaluar: Si tiene eventos → Evento-based, Sino → Score-based
            └─ result_verified_at = now()
               ✅ Resultado: Todas las preguntas evaluadas


VENTAJAS DE ESTA ARQUITECTURA:
==============================

1. ✅ SEPARACIÓN DE CONCERNS
   - Obtener datos (ProcessMatchBatchJob)
   - Enriquecer datos (ExtractMatchDetailsJob)
   - Verificar preguntas (VerifyQuestionResultsJob)

2. ✅ RESILIENCIA
   - Si Gemini falla en getDetailedMatchData: Match sigue siendo procesable
   - Si no hay eventos: Preguntas score-based se verifican igual

3. ✅ TIMING ÓPTIMO
   - Phase 1: Datos básicos en < 10s
   - Phase 2: Intenta detalles con delay (Gemini web search puede ser lento)
   - Phase 3: Verifica cuando datos estén disponibles

4. ✅ DEBUGGING CLARO
   - Cada job tiene responsabilidad única
   - Logs son específicos y trazables

5. ✅ ESCALABILIDAD
   - Si Gemini está lento, no bloquea pregunta verification
   - Cada job puede ejecutarse independientemente
   - Chunking ya está en VerifyQuestionResultsJob

6. ✅ COMPATIBLE CON ACTUAL
   - No requiere cambios a BD
   - Usa mismas tablas y campos
   - ProcessMatchBatchJob y VerifyQuestionResultsJob ya existen


IMPLEMENTACIÓN:
===============

PASO 1: Crear ExtractMatchDetailsJob
   ├─ Crear: app/Jobs/ExtractMatchDetailsJob.php
   ├─ Lógica: Buscar matches sin eventos, llamar Gemini, guardar
   └─ Timeout: 300s, tries: 3

PASO 2: Modificar ProcessMatchBatchJob
   ├─ Remover getDetailedMatchData() call
   ├─ Mantener solo getMatchResult() call
   └─ Simplificar lógica: solo guardar score + basic text

PASO 3: Actualizar ProcessRecentlyFinishedMatchesJob (Coordinador)
   ├─ Agregar dispatch de ExtractMatchDetailsJob (+10s)
   ├─ Mantener delay de VerifyQuestionResultsJob (+5min)
   └─ Order: UpdateFinishedMatchesJob → ProcessMatchBatchJob → ExtractMatchDetailsJob → VerifyQuestionResultsJob

PASO 4: Mejorar QuestionEvaluationService
   ├─ Ya tiene hasVerifiedMatchData()
   ├─ Ya tiene lógica condicional
   └─ Solo agregar logs mejorados

PASO 5: Opcional - Job para reprocesar preguntas antiguas
   ├─ Ejecutar VerifyQuestionResultsJob nuevamente
   ├─ Para preguntas donde result_verified_at = null
   └─ Podría tener nuevos eventos si ExtractMatchDetailsJob agregó


ESTIMACIÓN:
===========

Tiempo implementación: 30-45 min
   - Crear ExtractMatchDetailsJob: 10 min
   - Modificar ProcessMatchBatchJob: 5 min
   - Actualizar ProcessRecentlyFinishedMatchesJob: 5 min
   - Tests y debugging: 15-20 min

Beneficio:
   - 100% de preguntas verificables (score-based mínimo)
   - 90%+ de eventos disponibles si Gemini funciona
   - Sistema robusto ante fallos parciales
   - Debugging facilitado

¿Quieres que lo implemente?
