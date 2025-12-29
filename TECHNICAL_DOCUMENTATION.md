# Documentación Técnica - OffsideClub

## Descripción General del Proyecto

OffsideClub es una aplicación web desarrollada en Laravel para predicciones de fútbol, gestión de grupos sociales y competiciones interactivas. Permite a los usuarios crear grupos, hacer predicciones sobre partidos de fútbol, participar en preguntas sociales y competir en rankings basados en puntos.

## Arquitectura del Sistema

### Patrón Arquitectónico
- **MVC (Model-View-Controller)**: Implementado con Laravel Framework
- **ORM**: Eloquent ORM para interacción con base de datos
- **API-First**: Diseño orientado a APIs con Laravel Sanctum para autenticación
- **Job Queue**: Procesamiento asíncrono con Laravel Horizon y Redis
- **Broadcasting**: Notificaciones en tiempo real con Laravel Broadcasting

### Tecnologías Principales
- **Framework**: Laravel 10.x
- **Lenguaje**: PHP 8.1+
- **Base de Datos**: MySQL/PostgreSQL (con Doctrine DBAL)
- **Frontend**: Blade templates, Tailwind CSS, JavaScript/Vue.js
- **Cache/Queue**: Redis
- **Servidor Web**: Nginx (configurado para timeouts largos)
- **Autenticación**: Laravel Sanctum (API tokens)

## Entidades Principales (Modelos Eloquent)

### Usuario (User)
- **Propósito**: Representa a los usuarios registrados de la plataforma
- **Atributos clave**: name, email, password, theme, avatar, favorite_competition_id, is_admin
- **Relaciones**:
  - belongsToMany: roles
  - belongsToMany: groups
  - hasMany: answers, chatMessages, userReactions, groupRoles, feedback, questions
  - belongsTo: favoriteCompetition, favoriteClub, favoriteNationalTeam
  - hasMany: pushSubscriptions

### Grupo (Group)
- **Propósito**: Grupos de usuarios para competiciones y predicciones compartidas
- **Atributos clave**: name, code, created_by, competition_id, category, reward_or_penalty
- **Relaciones**:
  - belongsTo: creator (User), competition
  - belongsToMany: users
  - hasMany: questions, chatMessages, groupRoles, answers, templateQuestions
- **Funcionalidades**: Rankings de usuarios, predicciones pendientes

### Pregunta (Question)
- **Propósito**: Preguntas de predicción o sociales para partidos o eventos
- **Atributos clave**: title, description, type, points, available_until, group_id, match_id, is_featured, category, result_verified_at
- **Relaciones**:
  - belongsTo: group, football_match, templateQuestion, competition, user
  - hasMany: options (QuestionOption), answers, chatMessages

### Partido de Fútbol (FootballMatch)
- **Propósito**: Almacena datos de partidos obtenidos de APIs externas
- **Atributos clave**: external_id, home_team, away_team, date, status, score, competition_id, season
- **Relaciones**:
  - belongsTo: competition, stadium
  - belongsTo: homeTeam, awayTeam (Team)
  - hasMany: questions

### Respuesta (Answer)
- **Propósito**: Respuestas de usuarios a preguntas
- **Relaciones**: belongsTo: user, question, questionOption

### Equipo (Team)
- **Propósito**: Equipos de fútbol (clubes y selecciones nacionales)
- **Atributos clave**: name, crest, country, founded, venue

### Competición (Competition)
- **Propósito**: Campeonatos y torneos de fútbol
- **Atributos clave**: name, code, emblem, current_season

### Mensaje de Chat (ChatMessage)
- **Propósito**: Mensajes en chats de grupos
- **Relaciones**: belongsTo: user, group, question

### Otras Entidades
- **QuestionOption**: Opciones para preguntas múltiples
- **PushSubscription**: Suscripciones para notificaciones push
- **Role/GroupRole**: Sistema de roles y permisos
- **Feedback**: Comentarios de usuarios
- **TemplateQuestion**: Plantillas para preguntas recurrentes
- **Player**: Jugadores de equipos
- **Stadium**: Estadios
- **UserReaction**: Reacciones de usuarios

## Módulos Principales

### Controladores (Controllers)

#### Gestión de Usuarios
- **ProfileController**: Perfiles de usuario, configuración
- **SettingsController**: Configuraciones generales
- **Auth/LoginController**: Autenticación

#### Gestión de Grupos
- **GroupController**: CRUD de grupos, unirse/salir de grupos

#### Predicciones y Preguntas
- **QuestionController**: Gestión de preguntas y respuestas
- **RankingController**: Rankings y puntuaciones

#### Comunicación
- **ChatController**: Mensajes de chat en grupos
- **PushSubscriptionController**: Gestión de notificaciones push

#### Administración
- **Admin/CompetitionController**: Gestión de competiciones
- **Admin/QuestionAdminController**: Administración de preguntas
- **Admin/TemplateQuestionController**: Gestión de plantillas

### Servicios (Services)

#### Integración con APIs Externas
- **FootballService**: Interfaz principal con API de fútbol
- **FootballDataService**: Servicio específico para datos de fútbol
- **OpenAIService**: Integración con OpenAI para generación de preguntas

#### Notificaciones
- **FCMService**: Firebase Cloud Messaging para push notifications

#### Lógica de Negocio
- **GroupRoleService**: Gestión de roles en grupos
- **FeaturedMatchService**: Gestión de partidos destacados

## Integraciones Externas

### APIs de Fútbol
- **Football Data API**: Proveedor principal de datos de partidos, equipos y competiciones
- **Configuración**: `FOOTBALL_API_KEY` en config/services.php

### Inteligencia Artificial
- **OpenAI API**: Generación automática de preguntas predictivas y sociales
- **Configuración**: `OPENAI_API_KEY`, `OPENAI_ORGANIZATION` en config/openai.php

### Notificaciones Push
- **Firebase Cloud Messaging (FCM)**: Envío de notificaciones push a dispositivos móviles
- **Configuración**: `FCM_SERVER_KEY` en config/services.php

### Servicios de Correo
- **Mailgun**: Servicio de envío de emails
- **Postmark**: Servicio alternativo de emails
- **AWS SES**: Servicio de emails en la nube

### Almacenamiento y Cache
- **Redis**: Cache y colas de trabajos
- **Laravel Horizon**: Dashboard para gestión de colas

## Trabajos en Segundo Plano (Jobs)

### Procesamiento de Datos
- **UpdateMatchesAndVerifyResults**: Actualiza partidos y verifica resultados
- **ProcessRecentlyFinishedMatchesJob**: Procesa partidos finalizados recientemente
- **ProcessMatchBatchJob**: Procesa lotes de partidos

### Generación de Contenido
- **CreatePredictiveQuestionsJob**: Crea preguntas predictivas automáticamente

### Notificaciones
- **SendNewPredictiveQuestionsPushNotification**: Notifica nuevas preguntas predictivas
- **SendPredictiveResultsPushNotification**: Notifica resultados de predicciones
- **SendSocialQuestionPushNotification**: Notifica preguntas sociales
- **SendChatPushNotification**: Notifica mensajes de chat

### Verificación y Puntuación
- **VerifyQuestionResultsJob**: Verifica resultados de preguntas
- **UpdateAnswersPoints**: Actualiza puntos de respuestas

## Flujo de Datos Típico

1. **Actualización de Partidos**: Jobs periódicamente consultan la API de fútbol y actualizan la base de datos
2. **Generación de Preguntas**: OpenAI genera preguntas basadas en partidos próximos
3. **Interacción de Usuarios**: Usuarios responden preguntas en grupos
4. **Verificación de Resultados**: Al finalizar partidos, se verifican respuestas y asignan puntos
5. **Notificaciones**: FCM envía push notifications sobre nuevos eventos
6. **Rankings**: Se calculan rankings en tiempo real para grupos

## Características Técnicas Destacadas

### Optimización de Rendimiento
- **Queues con Redis**: Procesamiento asíncrono de tareas pesadas
- **Cache**: Uso extensivo de cache para datos de API
- **Lazy Loading**: Relaciones Eloquent optimizadas
- **Timeouts Configurados**: Nginx y PHP configurados para operaciones largas

### Seguridad
- **Laravel Sanctum**: Autenticación API stateless
- **Middleware**: Protección de rutas y roles
- **Validación**: Form requests para validación de entrada

### Escalabilidad
- **Job Batching**: Procesamiento de lotes para operaciones masivas
- **Rate Limiting**: Control de uso de APIs externas
- **Soft Deletes**: Eliminación lógica para integridad de datos

Esta documentación proporciona una visión técnica completa de la arquitectura y componentes del sistema OffsideClub.
