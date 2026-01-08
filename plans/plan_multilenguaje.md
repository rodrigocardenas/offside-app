# Plan de Implementación de Multilenguaje en OffsideClub

## Objetivo
Convertir la aplicación a multilenguaje, reemplazando textos hardcoded en vistas y controladores por archivos de idioma. El español será el idioma por defecto, y se agregará soporte para inglés.

## Pasos Generales

### 1. Configuración Inicial de Laravel para Multilenguaje
- Crear directorios `resources/lang/es` y `resources/lang/en`
- Configurar el locale por defecto en `config/app.php` (ya debería estar en 'es')
- Agregar middleware para cambiar idioma basado en preferencia de usuario o sesión

### 2. Identificación de Textos Hardcoded
- **Vistas (Blade templates)**: Buscar textos en español en todos los archivos .blade.php
- **Controladores**: Buscar mensajes de alerta, errores y success en métodos que usan `with('message', ...)`
- **Modelos y Servicios**: Revisar si hay textos hardcoded en validaciones o lógica de negocio
- **JavaScript**: Revisar archivos JS por textos hardcoded (si los hay)

### 3. Creación de Archivos de Idioma
- Crear archivos de idioma como `messages.php`, `validation.php`, `auth.php`, etc.
- Extraer textos comunes y específicos por módulo

### 4. Reemplazo de Textos
- En vistas: Reemplazar textos con `{{ __('clave') }}` o `@lang('clave')`
- En controladores: Usar `__()` para mensajes
- Actualizar todas las vistas y controladores identificados

### 5. Agregar Selector de Idioma
- Modificar el modelo User para incluir campo `language`
- Actualizar SettingsController para manejar cambio de idioma
- Agregar UI en settings para seleccionar idioma
- Implementar middleware para setear locale

### 6. Pruebas y Validación
- Probar cambio de idioma
- Verificar que todos los textos se traduzcan correctamente
- Revisar textos en emails, notificaciones push, etc.

## Vistas a Modificar (Lista Preliminar)
Basado en la estructura actual, las siguientes vistas contienen textos hardcoded en español:

### Layouts y Componentes
- `layouts/app.blade.php`
- `layouts/navigation.blade.php`
- `components/app-layout.blade.php`
- `components/guest-layout.blade.php`
- `components/mobile-dark-layout.blade.php`
- `components/mobile-light-layout.blade.php`
- `components/bottom-navigation.blade.php`
- `components/header-profile.blade.php`

### Páginas Principales
- `dashboard.blade.php` (Grupos Activos, Preguntas Disponibles, Ranking Diario, etc.)
- `welcome.blade.php`
- `profile/edit.blade.php`
- `settings/index.blade.php`

### Grupos
- `groups/index.blade.php`
- `groups/create.blade.php`
- `groups/show.blade.php`
- `groups/show-unified.blade.php`
- `groups/predictive-results.blade.php`

### Componentes de Grupos
- `components/groups/group-card.blade.php`
- `components/groups/group-header.blade.php`
- `components/groups/group-chat.blade.php`
- `components/groups/ranking-section.blade.php`
- `components/groups/ranking-modal.blade.php`
- `components/groups/stats-bar.blade.php`
- `components/groups/player-rank-card.blade.php`

### Preguntas y Predicciones
- `questions/show.blade.php`
- `questions/results.blade.php`
- `components/predictions/prediction-card.blade.php`
- `components/predictions/prediction-options.blade.php`

### Chat
- `chat/index.blade.php`
- `chat/question.blade.php`
- `components/chat/chat-section.blade.php`
- `components/chat/chat-message.blade.php`
- `components/chat/chat-input.blade.php`

### Rankings
- `rankings/daily.blade.php`
- `rankings/group.blade.php`

### Mercado
- `market/index.blade.php`

### Admin
- `admin/dashboard.blade.php`
- `admin/questions/index.blade.php`
- `admin/template-questions/create.blade.php`
- `admin/template-questions/edit.blade.php`
- `admin/template-questions/index.blade.php`

### Autenticación
- `auth/login.blade.php`
- `auth/register.blade.php`

### Errores
- `errors/500.blade.php`
- `errors/api.blade.php`

### Competencias
- `competitions/index.blade.php`
- `competitions/create.blade.php`
- `competitions/edit.blade.php`

## Controladores a Revisar
- `SettingsController.php` (mensajes de configuración)
- `ProfileController.php`
- `GroupController.php`
- `QuestionController.php`
- `ChatController.php`
- `RankingController.php`
- `CompetitionController.php`
- `Admin/CompetitionController.php`
- `Admin/QuestionAdminController.php`
- `Admin/TemplateQuestionController.php`
- `Auth/LoginController.php`
- `Auth/RegisterController.php` (si existe)

## Archivos de Idioma Sugeridos
- `resources/lang/es/messages.php` - Mensajes generales
- `resources/lang/es/auth.php` - Autenticación
- `resources/lang/es/validation.php` - Validaciones
- `resources/lang/es/pagination.php` - Paginación
- `resources/lang/es/groups.php` - Textos de grupos
- `resources/lang/es/questions.php` - Textos de preguntas
- `resources/lang/es/rankings.php` - Textos de rankings
- `resources/lang/es/chat.php` - Textos de chat
- `resources/lang/es/market.php` - Textos de mercado
- `resources/lang/es/admin.php` - Textos de admin

## Manejo de Contenido Dinámico (Preguntas y Plantillas)

### Contexto
Las preguntas y plantillas de preguntas se almacenan en la base de datos con campos como `title` y `description`. Estos textos dinámicos necesitan ser multilenguaje.

### Opciones Consideradas

#### Opción 1: Campos separados por idioma
- Agregar campos `title_en`, `description_en` en las tablas `questions` y `template_questions`
- Ventajas: Simple, rápido acceso, fácil mantenimiento
- Desventajas: Duplicación de datos, requiere migración de datos existentes

#### Opción 2: Tabla de traducciones
- Crear tabla `translations` con campos: `model`, `model_id`, `field`, `language`, `value`
- Ventajas: Flexible, escalable, evita duplicación
- Desventajas: Consultas más complejas, mayor overhead

#### Opción 3: Traducción en tiempo real
- Almacenar siempre en español, traducir al inglés cuando el usuario lo requiera
- Ventajas: Mantiene datos centralizados
- Desventajas: Requiere servicio de traducción (manual o API), potencial delay, costo si API

### Recomendación
Usar **Opción 1 (campos separados)** por simplicidad y rendimiento. Es la más directa para una aplicación con pocos idiomas.

### Implementación
- Crear migración para agregar campos `_en` a las tablas relevantes
- Actualizar modelos para incluir fillable
- Modificar controladores para guardar ambas versiones
- En vistas, mostrar el campo correspondiente al idioma del usuario
- Para admin, permitir editar ambas versiones

## Consideraciones Adicionales
- Mantener consistencia en las claves de traducción
- Usar namespaces si es necesario (ej: `groups.title`)
- Revisar textos en emails y notificaciones
- Considerar textos en JavaScript si hay validaciones front-end
- Probar con diferentes navegadores y dispositivos

## Próximos Pasos
1. Crear estructura de directorios de idioma
2. Identificar y extraer textos de vistas críticas primero (dashboard, groups, questions)
3. Implementar selector de idioma
4. Reemplazar textos gradualmente por módulos
5. Testing completo
