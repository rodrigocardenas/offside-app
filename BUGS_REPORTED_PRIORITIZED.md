# 🐛 Bugs Reportados - Análisis y Priorización

**Fecha de Reporte:** 26 enero 2026  
**Total de Bugs:** 8 bugs críticos identificados  
**Status:** A resolver en orden de prioridad

---

## 📊 Resumen Ejecutivo

| Categoría | Bugs | Impacto | Prioridad |
|-----------|------|--------|-----------|
| **App Capacitor** | 5 bugs | Alto | 🔴 CRÍTICA |
| **Flujo Predictivo** | 2 bugs | Medio | 🟠 ALTA |
| **Interfaz/UX** | 2 bugs | Medio | 🟡 MEDIA |
| **Total** | **9 bugs** | - | - |

---

# 🔴 PRIORIDAD CRÍTICA - App Capacitor (5 bugs)

Estos bugs afectan la experiencia de la app móvil generada con Capacitor y deben resolverse primero.

## 1. ❌ Gesto/Botón Volver de Android No Funciona Correctamente

**Descripción:**  
El gesto o botón atrás nativo de Android no navega a la pantalla anterior, sino que vuelve siempre a la pantalla de inicio.

**Impacto:**  
- 🔴 Crítico: Rompe la navegación fundamental de la app
- Los usuarios no pueden navegar correctamente entre pantallas
- Experiencia degradada comparada con navegación web

**Ubicación del Código:**
- [capacitor.config.ts](capacitor.config.ts) - Configuración base de Capacitor
- Potencialmente en: Rutas de Angular/React, manejo de historial del navegador

**Causa Probable:**
- El stack de navegación de Capacitor no sincroniza correctamente con el historial del navegador
- Posible conflicto entre navegación Capacitor + navegación web

**Solución Recomendada:**
1. Implementar manejador de `backButton` nativo de Capacitor
2. Sincronizar con el stack de historial de la app web
3. Usar `history.back()` en lugar de rutas hard-coded

**Archivos Relacionados:**
- [capacitor.config.ts](capacitor.config.ts#L1)
- Componentes de enrutamiento principales

---

## 2. 🔗 Deep Links No Abren la App (Abren Web en su lugar)

**Descripción:**  
Al generar un link de invitación a un grupo, este envía a los usuarios a la app web en lugar de abrir la app móvil instalada.

**Impacto:**
- 🔴 Crítico: Falla la experiencia de onboarding social
- Los links compartidos no funcionan correctamente en la app
- Los usuarios nuevos no pueden unirse a grupos desde invitaciones

**Ubicación del Código:**
- [capacitor.config.ts](capacitor.config.ts#L1) - Configuración de deep links
- `AndroidManifest.xml` (si existe)
- Backend: Generación de links de invitación

**Causa Probable:**
- Deep links no configurados en Capacitor
- Falta de `intent-filter` en Android
- URLs no están asociadas a la app correctamente

**Solución Recomendada:**
1. Configurar deep links en `capacitor.config.ts`
2. Agregar `intent-filter` en `AndroidManifest.xml`
3. Implementar manejador de rutas para deep links
4. Usar App Links (Android) para mejor seguridad
5. Configurar Universal Links (iOS)

**Archivos Relacionados:**
- [capacitor.config.ts](capacitor.config.ts#L1)
- Backend: Generación de links de invitación

---

## 3. 🔔 Notificaciones Firebase Solo Llegan a Web App, No a Mobile App

**Status:** 🟡 **EN PROGRESO** - 87% Completado (4 feb 2026)

**Descripción:**  
Las notificaciones push configuradas con Firebase solo se reciben en la web app, no en la app móvil generada con Capacitor.

**Impacto:**
- 🔴 Crítico: Las notificaciones no alertan a usuarios de app móvil
- Pérdida de engagement en usuarios de app móvil
- Sistema de notificaciones completo no funciona en mobile

**Ubicación del Código:**
- [public/sw.js](public/sw.js) - Service Worker principal
- [public/firebase-messaging-sw.js](public/firebase-messaging-sw.js) - SW de Firebase
- [app/Jobs/SendNewPredictiveQuestionsPushNotification.php](app/Jobs/SendNewPredictiveQuestionsPushNotification.php)
- [app/Jobs/SendPredictiveResultsPushNotification.php](app/Jobs/SendPredictiveResultsPushNotification.php)
- [app/Jobs/SendChatPushNotification.php](app/Jobs/SendChatPushNotification.php)

**Causa Probable:**
- Service Workers no se registran correctamente en contexto Capacitor
- Firebase Messaging no está integrado con Capacitor App
- Falta de `capacitor-google-play-services` o plugins similares

**Solución Implementada (Fase 1-4):**

### ✅ 1. Dependencias Instaladas
- `@capacitor-firebase/messaging@^6.1.2` ✅
- `@capacitor/device@^6.0.1` ✅

### ✅ 2. Configuración Base
- `capacitor.config.ts` con FirebaseMessaging ✅
- Migration para agregar `platform` field ✅

### ✅ 3. Frontend Unificado
- `public/js/firebase-notification-service.js` ✅
- Auto-detección web vs Capacitor ✅
- Manejo foreground/background ✅
- Sincronización automática de tokens ✅

### ✅ 4. Backend Refactorizado
- `app/Traits/HandlesPushNotifications.php` trait ✅
- Todos 4 Jobs actualizados ✅
- Código reducido 60% (eliminar duplicación) ✅
- Soporte para web, Android, iOS ✅

### 🟡 5. Testing (Próximo)
- [ ] Testing en web
- [ ] Testing en Android
- [ ] Testing en iOS

**Documentación:**
- [BUG3_FIREBASE_MOBILE_PUSH_ANALYSIS.md](BUG3_FIREBASE_MOBILE_PUSH_ANALYSIS.md) - Análisis técnico
- [IMPLEMENTATION_BUG3_FIREBASE_MOBILE_PUSH_PHASE_1-4.md](IMPLEMENTATION_BUG3_FIREBASE_MOBILE_PUSH_PHASE_1-4.md) - Implementación
- [TESTING_BUG3_FIREBASE_MOBILE_PUSH.md](TESTING_BUG3_FIREBASE_MOBILE_PUSH.md) - Guía de testing
- [BUG3_EXECUTIVE_SUMMARY.md](BUG3_EXECUTIVE_SUMMARY.md) - Resumen ejecutivo
- [WORK_SUMMARY_BUG3_SESSION.md](WORK_SUMMARY_BUG3_SESSION.md) - Resumen de sesión

**Rama:** `feature/bug3-firebase-notifications`  
**Commits:** 3 (537078f, 2316b6d, 86ff859)  
**Archivos Creados:** 4 + 5 docs  
**Archivos Modificados:** 10  

**Próximos Pasos:**
1. Compilar APK Android con google-services.json
2. Testing en web/Android/iOS
3. Resolver issues si hay
4. Merge a main
5. Deploy a producción

---

## 4. 💾 Contenido en App No Se Actualiza Sin `artisan cache:clear`

**Descripción:**  
El contenido mostrado en la app móvil no se actualiza automáticamente. Solo se actualiza después de ejecutar `artisan cache:clear` manualmente.

**Impacto:**
- 🔴 Crítico: Los usuarios ven contenido obsoleto
- Las actualizaciones de datos no se reflejan en tiempo real
- Experiencia consistentemente desactualizada

**Ubicación del Código:**
- [capacitor.config.ts](capacitor.config.ts) - Configuración de caché
- Backend: Estrategia de caché
- Posible: `config/cache.php`

**Causa Probable:**
- Caché agresivo configurado en Capacitor/Android
- Las invalidaciones de caché no se propagan correctamente
- Falta de cache-busting en las solicitudes

**Solución Recomendada:**
1. Desabilitar/Reducir agresividad del caché en Capacitor
2. Implementar cache-busting (query parameters con timestamps)
3. Usar `Cache-Control` headers apropiados
4. Implementar polling o WebSocket para actualizaciones

**Archivos Relacionados:**
- [capacitor.config.ts](capacitor.config.ts#L1)
- Backend cache configuration

---

## 5. 📱 Pull-to-Refresh No Está Disponible en App (Solo en Web)

**Status:** ✅ **RESUELTO** (26 enero 2026)

**Descripción:**  
En la web, el gesto de recarga (swipe sostenido desde arriba) funciona correctamente. En la app móvil, este gesto no está disponible para actualizar la página.

**Impacto:**
- 🟠 Alto: Experiencia mobile degrada comparada a web
- Los usuarios mobile no pueden recargar manualmente
- Dependen completamente de la actualización automática

**Solución Implementada:**

### Librería Vanilla JavaScript
✅ Creada clase `OffsidePullToRefresh` en [public/js/pull-to-refresh.js](public/js/pull-to-refresh.js):
- Touch events para mobile
- Indicador visual responsivo
- Icono que rota con progreso
- Spinner durante recarga
- Sin dependencias externas

### Integración
✅ Script incluido en [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php):
- Detecta automáticamente mobile/Capacitor
- No interfiere en desktop
- Inicializa sin configuración manual

### Backend
✅ Nuevo endpoint [POST /api/cache/clear-user](routes/api.php):
- Limpia cache del usuario
- Limpia cache de todos sus grupos
- Protegido con auth:sanctum
- Fallback a page reload

**Características:**
- ✅ Detección automática de mobile
- ✅ Indicador visual con color dinámico
- ✅ Threshold 80px para activar
- ✅ Cache limpiado automáticamente
- ✅ Confirmación visual de éxito

**Archivos Modificados:**
- [public/js/pull-to-refresh.js](public/js/pull-to-refresh.js) - Creado
- [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php#L49-L50)
- [routes/api.php](routes/api.php#L38-L54)

**Documentación:**
- [IMPLEMENTATION_BUG5_PULL_TO_REFRESH.md](IMPLEMENTATION_BUG5_PULL_TO_REFRESH.md) - Análisis completo

---

# 🟠 PRIORIDAD ALTA - Flujo de Preguntas Predictivas (2 bugs)

Estos bugs impactan directamente la funcionalidad core de predicciones deportivas.

## 6. 🔄 Partidos Repetidos en Preguntas Predictivas

**Status:** ✅ **RESUELTO** (26 enero 2026)

**Descripción:**  
El sistema genera preguntas predictivas con la API Football sin validar que no se generen partidos/preguntas duplicadas. Resulta en preguntas repetidas.

**Impacto:**
- 🟠 Alto: Experiencia degradada, confunde al usuario
- Desperdicia datos de la API Football
- Lógica predictiva no confiable

**Root Cause Identificado:**
1. En `fillGroupPredictiveQuestions()` línea 218: Solo verificaba preguntas vigentes (`available_until > now()`)
2. Cuando una pregunta expiraba (hace 5 min), se creaba OTRA para el mismo partido
3. En `createQuestionFromTemplate()` línea 335: `firstOrCreate()` usaba `title` como clave, no `match_id` + `group_id`

**Solución Implementada:**

### 1. Actualizar Validación de Duplicados
✅ [app/Traits/HandlesQuestions.php](app/Traits/HandlesQuestions.php#L218-L226):
- Cambio: `available_until > now()` → `created_at > now()->subHours(24)`
- Ahora considera preguntas expiradas en las últimas 24 horas
- Previene crear pregunta si existe una reciente del mismo partido

### 2. Mejorar `firstOrCreate()` con Claves Correctas
✅ [app/Traits/HandlesQuestions.php](app/Traits/HandlesQuestions.php#L335-L348):
- Cambio: Claves `(title, group_id, match_id, template_question_id)` → `(match_id, group_id, template_question_id)`
- Garantiza unicidad por (`match_id`, `group_id`, `template_question_id`)
- Idempotente: job puede ejecutarse múltiples veces sin duplicados

### 3. Validación en Model (Boot Hook)
✅ [app/Models/Question.php](app/Models/Question.php#L32-L58):
- Nuevo `boot()` method con validación en `creating()`
- Verifica que no exista pregunta predictiva para (match_id, group_id) en últimas 24h
- Lanza Exception si intenta crear duplicada
- Registra en logs intentos bloqueados

**Características:**
- ✅ Preguntas expiradas no bloquean nuevas del mismo partido
- ✅ Job idempotente: puede ejecutarse N veces sin duplicados
- ✅ Protección de 3 capas: query filter + firstOrCreate keys + model validation
- ✅ Preguntas sociales NO se ven afectadas
- ✅ Logs registran intentos de duplicados

**Archivos Modificados:**
- [app/Traits/HandlesQuestions.php](app/Traits/HandlesQuestions.php) - Deduplicación en trait
- [app/Models/Question.php](app/Models/Question.php) - Validación en modelo

**Documentación:**
- [IMPLEMENTATION_BUG6_DUPLICATE_QUESTIONS.md](IMPLEMENTATION_BUG6_DUPLICATE_QUESTIONS.md) - Análisis técnico
- [TESTING_BUG6_DUPLICATE_PREVENTION.md](TESTING_BUG6_DUPLICATE_PREVENTION.md) - Guía de testing

---

## 7. ⏰ Actualización de Resultados y Verificación de Preguntas Falla

**Status:** ✅ **RESUELTO** (26 enero 2026)

**Descripción:**  
La actualización de resultados de partidos (cada hora) no funciona correctamente. Tampoco funciona la verificación posterior de preguntas y asignación de puntos.

**Impacto:**
- 🟠 Alto: El sistema core de preguntas predictivas está roto
- Los usuarios no reciben puntos correctamente
- Las preguntas no se marcan como contestadas/finalizadas

**Root Cause Identificado:**

### Flujo en Cascada
```
:00 → UpdateFinishedMatchesJob (Despacha ProcessMatchBatchJob)
:05 → VerifyFinishedMatchesHourlyJob (Busca partidos finalizados)
     → VerifyAllQuestionsJob (Asigna puntos)
```

### 4 Problemas Críticos

1. **Timeout insuficiente:** ProcessMatchBatchJob timeout=120s, pero Gemini tardaba 30-60s × 5 partidos = 150-300s
2. **Sin reintentos:** BatchGetScoresJob tries=1, una falla en Gemini = job completo fallaba
3. **Timing gap:** VerifyFinishedMatchesHourlyJob :05 se ejecutaba antes de que ProcessMatchBatchJob terminara
4. **Sin validación:** `$match->update()` fallaba silenciosamente, datos no persistidos

**Solución Implementada:**

### 1️⃣ Aumentar Timeout
✅ [app/Jobs/ProcessMatchBatchJob.php](app/Jobs/ProcessMatchBatchJob.php#L17):
- timeout: 120s → 300s (5 minutos)
- Dar tiempo a Gemini web search

### 2️⃣ Agregar Reintentos
✅ [app/Jobs/BatchGetScoresJob.php](app/Jobs/BatchGetScoresJob.php#L24):
- tries: 1 → 3
- 3 intentos para recuperar de fallos Gemini

### 3️⃣ Aumentar Timing Gap
✅ [app/Console/Kernel.php](app/Console/Kernel.php#L47):
- VerifyFinishedMatchesHourlyJob: `:05` → `:15` (15 minutos)
- Garantiza que ProcessMatchBatchJob completó

### 4️⃣ Validar Persistencia
✅ [app/Jobs/ProcessMatchBatchJob.php](app/Jobs/ProcessMatchBatchJob.php#L132-141):
```php
$updated = $match->update($updateData);
if (!$updated) {
    throw new Exception("Failed to update match");
}
```
- Verifica que update() funcionó
- Reintentos si falla

### 5️⃣ Health Check Automático
✅ [app/Jobs/VerifyBatchHealthCheckJob.php](app/Jobs/VerifyBatchHealthCheckJob.php) (NUEVO):
- Se ejecuta cada hora `:20` (después del ciclo)
- Monitorea:
  - ¿Partidos sin finalizar?
  - ¿Preguntas sin verificar?
  - ¿Respuestas sin puntos?
  - ¿Errores en logs?
- Alerta si anomalías

**Timeline Resultante:**
```
:00 → UpdateFinishedMatchesJob (despacha ProcessMatchBatchJob)
:10-:14 → ProcessMatchBatchJob procesando lotes (timeout: 300s)
:15 → VerifyFinishedMatchesHourlyJob (busca partidos finalizados + asigna puntos)
:20 → VerifyBatchHealthCheckJob (monitoreo de salud)
```

**Características:**
- ✅ Timeout suficiente para Gemini
- ✅ Reintentos automáticos en fallos
- ✅ Timing garantizado entre jobs
- ✅ Validación de persistencia
- ✅ Monitoreo proactivo
- ✅ Logs detallados para debugging

**Archivos Modificados:**
- [app/Jobs/ProcessMatchBatchJob.php](app/Jobs/ProcessMatchBatchJob.php) - timeout 300s, validación update()
- [app/Jobs/BatchGetScoresJob.php](app/Jobs/BatchGetScoresJob.php) - tries=3
- [app/Console/Kernel.php](app/Console/Kernel.php) - timing gap :15, health check
- [app/Jobs/VerifyBatchHealthCheckJob.php](app/Jobs/VerifyBatchHealthCheckJob.php) - NUEVO

**Documentación:**
- [BUG7_FLOW_ANALYSIS.md](BUG7_FLOW_ANALYSIS.md) - Análisis del flujo completo
- [BUG7_SOLUTIONS.md](BUG7_SOLUTIONS.md) - Problemas y soluciones
- [IMPLEMENTATION_BUG7_COMPLETE.md](IMPLEMENTATION_BUG7_COMPLETE.md) - Implementación

---

# 🟡 PRIORIDAD MEDIA - Interfaz/UX (2 bugs)

Estos bugs impactan la UX pero no rompen funcionalidad crítica.

## 8. ⏱️ Hora del Partido Muestra Zona Horaria de App (Madrid) No del Dispositivo

**Status:** ✅ **RESUELTO** (26 enero 2026)

**Descripción:**  
En el show de grupos, cuando se desplegaba la card de preguntas predictivas, la hora del partido se mostraba en la zona horaria de la app (Madrid UTC+1), no en la zona horaria del dispositivo del usuario.

**Impacto:**
- 🟡 Medio: Confunde al usuario sobre cuándo es el partido
- Especialmente problemático para usuarios en zonas horarias lejanas
- Los usuarios pueden perder preguntas por "timing"

**Solución Implementada:**

### Backend
✅ Nuevo método `toUserTimestampForCountdown()` en DateTimeHelper:
- Convierte UTC → zona horaria del usuario
- Retorna formato legible para JavaScript (Y-m-d H:i:s)
- Usa `Auth::user()->timezone` si existe

### Frontend
✅ Nuevo Blade directive `@userTimestamp()`:
- Reemplaza hardcoded `.timezone('Europe/Madrid')`
- 3 vistas actualizadas (group-match-questions x2, group-social-question)
- Countdown.js recibe hora correcta por zona

✅ **Horas de partidos ya correctas:**
- Ya usaban `@userTime()` en líneas 60 y 72 de group-match-questions.blade.php

**Archivos Modificados:**
- [app/Helpers/DateTimeHelper.php](app/Helpers/DateTimeHelper.php#L103-L147)
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L52-L54)
- [resources/views/components/groups/group-match-questions.blade.php](resources/views/components/groups/group-match-questions.blade.php#L162,L237)
- [resources/views/components/groups/group-social-question.blade.php](resources/views/components/groups/group-social-question.blade.php#L158)

**Documentación:**
- [IMPLEMENTATION_BUG8_TIMEZONE.md](IMPLEMENTATION_BUG8_TIMEZONE.md) - Análisis completo

---

## 9. 🔒 Preguntas No Se Bloquean Cuando el Partido Arranca

**Status:** ✅ **RESUELTO** (26 enero 2026)

**Descripción:**  
Actualmente no hay validación para bloquear las preguntas predictivas una vez que el partido ha comenzado. Los usuarios pueden responder preguntas incluso cuando el partido ya está en juego.

**Impacto:**
- 🟡 Medio: Afecta equidad de las predicciones
- Los usuarios "ingenieros" pueden ver resultados parciales
- Lógica de predicción comprometida

**Solución Implementada:**

### Backend (QuestionController::answer)
✅ Agregada validación que verifica:
- Si es pregunta predictiva
- Si el partido ya ha comenzado (`football_match->date <= now()`)
- Si intenta responder → Lanza excepción "match_already_started"
- Registra intentos en logs para auditoría

### Frontend (group-match-questions.blade.php)
✅ Componente ahora:
- Detecta cuando el partido ha comenzado
- Muestra banner rojo prominente con icono 🔒
- Oculta el formulario de respuesta
- Muestra respuesta anterior (si existe)
- Transición suave a vista de resultados

**Archivos Modificados:**
- [app/Http/Controllers/QuestionController.php](app/Http/Controllers/QuestionController.php#L95-L118)
- [resources/views/components/groups/group-match-questions.blade.php](resources/views/components/groups/group-match-questions.blade.php#L84-L108)

**Documentación:**
- [IMPLEMENTATION_BUG9_BLOCK_PREDICTIONS.md](IMPLEMENTATION_BUG9_BLOCK_PREDICTIONS.md) - Análisis completo
- [TESTING_BUG9_QUICK_REFERENCE.md](TESTING_BUG9_QUICK_REFERENCE.md) - Casos de prueba

---

## 📋 Plan de Acción Recomendado

### Fase 1: Bugs Críticos (1-2 semanas)
1. ✅ Gesto Back de Android
2. ✅ Deep Links (unirse a grupos)
3. ✅ Firebase Notificaciones en Mobile
4. ✅ Cache en App Mobile
5. ✅ Pull-to-Refresh

### Fase 2: Flujo Predictivo (1-2 semanas)
6. ✅ Partidos Repetidos
7. ✅ Actualización de Resultados/Verificación

### Fase 3: UX/Polish (3-5 días)
8. ✅ Zona Horaria en Preguntas
9. ✅ Bloqueo de Preguntas Post-Inicio

---

## 🔧 Recursos Útiles

- **Documentación Capacitor:** https://capacitorjs.com/docs
- **Firebase + Capacitor:** https://capacitorjs.com/solution/firebase
- **Deep Links en Capacitor:** https://capacitorjs.com/docs/plugins/app-links
- **DateTimeHelper Existente:** [app/Helpers/DateTimeHelper.php](app/Helpers/DateTimeHelper.php)

---

## 📝 Notas

- La mayoría de bugs están interconectados (ej: notificaciones afectan actualización)
- Algunos bugs pueden tener causas raíz comunes (configuración Capacitor)
- Considerar realizar auditoría de configuración Capacitor como primer paso
