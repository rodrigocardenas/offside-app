# 📊 Roadmap Admin Panel — General Dashboard & CRUDs

## Objetivo General
Extender el panel administrativo con visibilidad transversal de la actividad de la app (preguntas, usuarios y sesiones) y con mantenedores (BREAD/CRUD) para catálogos clave (equipos, competiciones, etc.), manteniendo consistencia con el ecosistema Laravel/Tailwind ya existente.

---

## Fase 6 · Dashboard General de Actividad

### 1. Métricas y tarjetas prioritarias
| Métrica | Descripción | Fuente | Notas |
| --- | --- | --- | --- |
| Preguntas respondidas (últimas 24 h / 7 d) | Total de respuestas registradas en `answers` | Tabla `answers` (agrupación por rango) | Permite ver engagement puntual y semanal. |
| Preguntas verificadas (últimas 24 h) | Conteo de `questions.result_verified_at` | Tabla `questions` | Ya se usan métricas similares en dashboard de verificación. |
| Usuarios nuevos | Registro diario/semanal de `users.created_at` | `users` | Mostrar tendencia (sparklines). |
| Usuarios activos / logins recientes | Últimos accesos (tabla `sessions` o log centralizado) | Definir fuente: `sessions`, `personal_access_tokens`, o tabla custom | Si no existe, crear tabla `user_logins`. |
| Retención rápida | % de usuarios que respondieron ≥1 pregunta en los últimos 7 días | Join `answers` + `users` | Calcular en job nocturno o consulta agregada. |

### 2. Feed y tablas
- **Últimas preguntas contestadas**
  - Query `answers` con `user_id`, `question_id`, `created_at`.
  - Incluir resultado (correcta/incorrecta), puntos otorgados, grupo.
  - Enriquecer con `questions.title`, `users.unique_id`.

- **Últimos usuarios registrados**
  - Lista (10) ordenada por `created_at desc`, campos: nombre, email, país (si existe), método de registro.
  - Mostrar badges para rol admin/mod.

- **Usuarios con sesión iniciada recientemente**
  - Fuente ideal: tabla `user_sessions`/`user_logins` (ver sección 3). Mostrar device, IP, timestamp.

### 3. Recolección de datos de sesión (si no existe)
- Crear middleware/listener que al autenticar grabe en tabla `user_logins`:
  - `user_id`, `ip_address`, `user_agent`, `logged_in_at`.
- Sembrar índice por `logged_in_at` para consultas rápidas.
- Integrar en dashboard con gráfico de barras (ingresos por hora/ día).

### 4. Backend/API
- Reutilizar patrón del dashboard de verificación: controlador `Admin\AppHealthDashboardController` + endpoint JSON `/admin/app-health-dashboard/data`.
- Servicios auxiliares:
  - `App\Services\Metrics\AnswerMetricsService`
  - `App\Services\Metrics\UserMetricsService`
- Caching: usar `Cache::remember` (TTL 60 s) para métricas agregadas pesadas (retención, totales semanales).

### 5. UI/UX
- Nueva vista Blade `resources/views/admin/app-health-dashboard.blade.php`.
- Mantener estilo “neo-noir” (gradientes oscuros + acentos esmeralda) pero diferenciarlo del dashboard de verificación.
- Componentes:
  1. Hero con resumen y selector de rango (24 h / 7 d / 30 d).
  2. Tarjetas métricas responsivas.
  3. Gráfico de barras/líneas (usar Chart.js o Alpine + `<canvas>` ligero) para usuarios nuevos vs activos.
  4. Tablas/feeds (preguntas contestadas, usuarios registrados, logins recientes) con auto-refresh opcional (60 s).

### 6. Seguridad y permisos
- Rutas bajo `Route::middleware(['auth','verified','role:admin'])`.
- Añadir entrada al menú admin existente.

### 7. Checklist de entrega
1. Migración `create_user_logins_table` (si no existe) + modelo.
2. Seeder opcional con datos mock para QA.
3. Controlador + servicios de métricas.
4. Vista Blade + JS para gráficos/live updates.
5. Pruebas manuales (seed, dashboards, endpoints JSON).

---

## Fase 7 · Mantenedores BREAD (Equipos, Competiciones, etc.)

### 1. Alcance inicial
| Módulo | Operaciones | Campos clave | Reglas |
| --- | --- | --- | --- |
| Equipos (`teams`) | Listar, crear, editar, eliminar (suave), buscar | nombre, liga, país, logo, slug | Validar unicidad por nombre+liga. Upload opcional de logo. |
| Competiciones (`competitions`) | CRUD completo + asignar equipos/temporadas | nombre, país, tipo, temporada actual | Relación con `teams`, `football_matches`. |
| Stadiums (opcional) | CRUD para sedes | nombre, ciudad, capacidad | Usado en `football_matches`. |

### 2. Arquitectura recomendada
- Reutilizar layout admin (`layouts.app`).
- Rutas en `routes/admin.php` usando `Route::resource` + políticas (`TeamPolicy`, `CompetitionPolicy`).
- Controladores dedicados (`Admin\TeamController`, `Admin\CompetitionController`).
- Requests form (`StoreTeamRequest`, `UpdateTeamRequest`) con validaciones.
- Componente Blade para formularios (inputs reutilizables, upload de logos con Livewire/Alpine si se requiere preview).

### 3. Funcionalidades extra
- **Búsqueda y filtros**: por nombre, país, liga (usar `scopeFilter` en modelos + query strings).
- **Paginación server-side** (simplePaginate 25 items).
- **Soft Deletes (opcional)** para evitar perder referencias en partidos/historial.
- **Accesos rápidos** desde el nuevo dashboard general (cards con enlaces a “Crear equipo”/“Crear competencia”).

### 4. Dependencias/consideraciones
- Confirmar relaciones en modelo `Team`/`Competition` (ex.: `Team` ya existe? si no, generarlo).
- Garantizar integridad referencial (foreign keys) para nuevas tablas/columnas.
- Revisar permisos actuales (`admin` vs otros roles). Si se necesitaran roles adicionales (ej. `moderator`), ajustarlos antes del release.

### 5. Entregables
1. Migraciones (si faltan campos, p. ej. `teams.slug`, `competitions.slug`).
2. Modelos/policies/requests actualizados.
3. Controladores + vistas Blade (index/listado, create/edit, show opcional).
4. Tests básicos (feature) para rutas CRUD críticas.
5. Documentación breve en `README_ADMIN.md` sobre cómo usar los mantenedores.

---

## Linea Temporal Sugerida
| Semana | Hitos |
| --- | --- |
| Semana 1 | Implementar tabla `user_logins`, servicios de métricas y API del dashboard general. UI inicial con tarjetas/feeds. |
| Semana 2 | Completar gráficos, auto-refresh y QA del dashboard. Documentar endpoints. |
| Semana 3 | CRUD de equipos (incluye validaciones, uploads). |
| Semana 4 | CRUD de competiciones + enlaces con equipos y matches. |
| Semana 5 | Ajustes finales, pruebas integrales y despliegue.

---

## Próximos pasos inmediatos
1. Validar con negocio el set final de métricas para el dashboard general (¿importa también monetización / uso de gemas?).
2. Confirmar si existe tabla/log para sesiones; de no existir, planificar la migración en la siguiente iteración.
3. Priorizar qué mantenedor se desarrolla primero (equipos vs competiciones) según urgencia operativa.
4. Crear tickets individuales por cada bloque (Dashboard General, CRUD Equipos, CRUD Competiciones) para seguimiento en el backlog.
