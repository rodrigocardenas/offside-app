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

**Solución Recomendada:**
1. Implementar `@capacitor-firebase/messaging` (plugin oficial)
2. Registrar device token con Firebase desde app native
3. Sincronizar tokens con backend
4. Manejador de notificaciones en foreground/background

**Archivos Relacionados:**
- [public/sw.js](public/sw.js#L97-L150)
- [public/firebase-messaging-sw.js](public/firebase-messaging-sw.js#L1)
- App Jobs de notificaciones

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

**Descripción:**  
El sistema genera preguntas predictivas con la API Football sin validar que no se generen partidos/preguntas duplicadas. Resulta en preguntas repetidas.

**Impacto:**
- 🟠 Alto: Experiencia degradada, confunde al usuario
- Desperdicia datos de la API Football
- Lógica predictiva no confiable

**Ubicación del Código:**
- Backend: Command/Job que genera preguntas predictivas
- Posible: `app/Console/Commands/` 
- API Football integration

**Causa Probable:**
- No existe validación de duplicados antes de crear preguntas
- Lógica de selección de partidos no filtra existentes
- Posible race condition en procesos paralelos

**Solución Recomendada:**
1. Antes de generar preguntas, verificar que no existan para ese partido
2. Usar `whereNotExists()` en queries
3. Agregar unique constraint en DB si no existe
4. Implementar transacciones para evitar race conditions

**Archivos Relacionados:**
- Backend: Comando generador de preguntas
- Modelos: PredictiveQuestion, Match

---

## 7. ⏰ Actualización de Resultados y Verificación de Preguntas Falla

**Descripción:**  
La actualización de resultados de partidos (cada hora) no funciona correctamente. Tampoco funciona la verificación posterior de preguntas y asignación de puntos.

**Impacto:**
- 🟠 Alto: El sistema core de preguntas predictivas está roto
- Los usuarios no reciben puntos correctamente
- Las preguntas no se marcan como contestadas/finalizadas

**Ubicación del Código:**
- Backend: Batch jobs/Commands que actualizan resultados
- [app/Jobs/SendPredictiveResultsPushNotification.php](app/Jobs/SendPredictiveResultsPushNotification.php)
- API Football integration
- Posible: Queue workers

**Causa Probable:**
- Fallo en la integración con API Football para obtener resultados
- Lógica de verificación de respuestas tiene errores
- Asignación de puntos no actualiza BD correctamente
- Timeout en Gemini grounding (si se usa)

**Solución Recomendada:**
1. Debuggear batch job de actualización de resultados
2. Validar respuestas de API Football
3. Revisar lógica de verificación de preguntas
4. Implementar logging exhaustivo en cada paso
5. Crear tests unitarios para el flujo completo

**Archivos Relacionados:**
- Backend: Batch jobs de resultados
- [app/Jobs/SendPredictiveResultsPushNotification.php](app/Jobs/SendPredictiveResultsPushNotification.php)

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
