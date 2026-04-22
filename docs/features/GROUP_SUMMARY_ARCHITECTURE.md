# 📐 Group Summary: Arquitectura & Flow Diagram

---

## 🏗️ Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                     OFFSIDE CLUB                            │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Frontend: groups/summary.blade.php                   │  │
│  │                                                       │  │
│  │ [💰 Total Pts] [👥 Members] [❓ Questions] [💬 Msgs] │  │
│  │                                                       │  │
│  │ ┌────────────────────┐  ┌────────────────────────┐  │  │
│  │ │  Top 10 Miembros   │  │ Distribución de Pts   │  │  │
│  │ │ 1. Juan  150K pts  │  │ 0-1K:  ████░░░░  (5)  │  │  │
│  │ │ 2. María 120K pts  │  │ 1-5K:  ██████░░ (10)  │  │  │
│  │ │ 3. Luis   95K pts  │  │ 5-10K: █████░░░  (8)  │  │  │
│  │ └────────────────────┘  └────────────────────────┘  │  │
│  │                                                       │  │
│  │ ┌────────────────────┐  ┌────────────────────────┐  │  │
│  │ │  Info del Grupo    │  │ Estadísticas           │  │  │
│  │ │ Código: ABC123     │  │ Max:    150K pts       │  │  │
│  │ │ Creador: Admin     │  │ Avg:    21K pts        │  │  │
│  │ │ Creado: 2026-01-15 │  │ Mediana: 18K pts       │  │  │
│  │ └────────────────────┘  └────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────┘  │
│                          ▲                                  │
│                          │ GET /groups/{id}/summary         │
└──────────────────────────┼──────────────────────────────────┘
                           │
                           │ Laravel Route
                           │
┌──────────────────────────┼──────────────────────────────────┐
│                          ▼                                  │
│  ┌────────────────────────────────────────────────────┐   │
│  │ GroupSummaryController::show()                     │   │
│  │                                                    │   │
│  │ ✅ Gate::authorize('viewSummary', $group)         │   │
│  │ ✅ getTopMembers($group, 10)                      │   │
│  │ ✅ getPointsDistribution($group)                  │   │
│  │ ✅ getMemberStats($group)                         │   │
│  │ ✅ return view('groups.summary', $stats)          │   │
│  └────────────────────────────────────────────────────┘   │
│                          ▲                                  │
│  Database Queries (Optimized with Indexes)                │
│                          │                                  │
└──────────┬───────────────┼───────────────┬─────────────────┘
           │               │               │
           ▼               ▼               ▼
    ┌────────────┐  ┌──────────────┐  ┌─────────────┐
    │   groups   │  │  group_user  │  │   users     │
    │ (caché)    │  │  (pivot)     │  │             │
    │            │  │              │  │             │
    │ id         │  │ group_id     │  │ id          │
    │ name       │  │ user_id      │  │ name        │
    │ code       │  │ points ◄─────┼──┤ email       │
    │ ...        │  │              │  │ avatar      │
    │ ⭐         │  │ [INDEX]      │  │             │
    │ total_pts  │  │              │  │             │
    │ updated_at │  └──────────────┘  └─────────────┘
    └────────────┘
           ▲
           │ Actualiza cada hora
           │
    ┌──────────────────────────────┐
    │  UpdateGroupTotalPointsJob   │
    │  Ejecuta: hourly             │
    │                              │
    │ FOR EACH group:              │
    │   total_pts = SUM(           │
    │     group_user.points        │
    │   )                          │
    │   group.save()               │
    └──────────────────────────────┘
           ▲
           │ Scheduler
           │
    ┌──────────────────────────────┐
    │    Kernel::schedule()        │
    │                              │
    │ $schedule                    │
    │   ->job(new UpdateJobJob)    │
    │   ->hourly()                 │
    └──────────────────────────────┘
```

---

## 🔄 Data Flow: De Respuesta a Resumen

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario responde pregunta                                │
└──────────────────────────────────────┬──────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Answer created {points_earned: 300, user_id: 5, ...}    │
│    (En tabla answers)                                       │
└──────────────────────────────────────┬──────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. VerifyAllQuestionsJob / VerifyQuestionResultsJob        │
│    - Verifica si respuesta es correcta                     │
│    - Actualiza answer.points_earned y answer.is_correct    │
│    - Llama syncGroupUserPoints()                           │
└──────────────────────────────────────┬──────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. group_user.points += pointsDiff                         │
│    (Pivot table actualizada en tiempo real)                │
│    Ej: 0 + 300 = 300                                       │
└──────────────────────────────────────┬──────────────────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    │                                     │
                    ▼                                     ▼
        ┌──────────────────────┐        ┌──────────────────────┐
        │ Usuario ve puntos    │        │ Cada HORA ejecuta    │
        │ en tiempo real en:   │        │ UpdateGroupTotalPts  │
        │                      │        │                      │
        │ - Podio              │        │ SUM(group_user.pts)  │
        │ - Ranking            │        │ = 300+200+500... 👈  │
        │ - Group View         │        │ groups.total_pts=... │
        └──────────────────────┘        └──────────────────────┘
                    │                            │
                    │                            ▼
                    │                  ┌──────────────────────┐
                    │                  │ Admin ve en RESUMEN: │
                    │                  │ Total de grupo: 5K   │
                    │                  │ actualizado a 11:22  │
                    │                  └──────────────────────┘
                    │                            ▲
                    └────────────────────────────┘
                         Ambos datos sincronizados
```

---

## 🎯 Permission & Authorization Flow

```
┌──────────────────────────────────────────┐
│ Usuario solicita: GET /groups/1/summary  │
└───────────────────┬──────────────────────┘
                    │
                    ▼
┌──────────────────────────────────────────┐
│ Middleware: auth (usuario autenticado)   │
│ ✅ PASS                                  │
└───────────────────┬──────────────────────┘
                    │
                    ▼
┌──────────────────────────────────────────┐
│ GroupSummaryController::show()           │
│ Gate::authorize('viewSummary', $group)   │
└───────────────────┬──────────────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
    ┌────────────┐        ┌────────────┐
    │ ¿Es creador?        │ ¿Es admin? │
    │ user_id ==          │ is_admin   │
    │ group.created_by    │ == true    │
    └────────┬────────────┴────┬───────┘
             │                 │
             ├─ YES ──┬────────┘
             │        │
             ▼        ▼
        ✅ PERMITIDO
        Mostrar resumen
        
             │
             NO
             │
             ▼
        ❌ DENEGADO (403)
        "No tienes permiso"
```

---

## 📊 Database State Diagram

```
┌────────────────────────────────────────────────────────────┐
│ Antes de UpdateGroupTotalPointsJob                         │
└────────────────────────────────────────────────────────────┘

    groups table:
    ┌─────────────────────────────┐
    │ id=1, name="Clásicos"       │
    │ total_points = 0 ❌ WRONG   │
    │ updated_at = NULL           │
    └─────────────────────────────┘
              │
              │ MISMATCH!
              │
    group_user table:
    ┌─────────────────────────────┐
    │ (group_id=1, user_id=1)     │
    │ points = 5000               │
    ├─────────────────────────────┤
    │ (group_id=1, user_id=2)     │
    │ points = 3000               │
    ├─────────────────────────────┤
    │ (group_id=1, user_id=3)     │
    │ points = 2000               │
    │                             │
    │ SUM = 10,000 ✅ CORRECT     │
    └─────────────────────────────┘


┌────────────────────────────────────────────────────────────┐
│ Después de UpdateGroupTotalPointsJob (Cada hora)           │
└────────────────────────────────────────────────────────────┘

    groups table:
    ┌─────────────────────────────┐
    │ id=1, name="Clásicos"       │
    │ total_points = 10000 ✅     │
    │ updated_at = 2026-04-22     │
    │               11:00:00      │
    └─────────────────────────────┘
              │
              │ SYNCRONIZED!
              │
    group_user table:
    ┌─────────────────────────────┐
    │ (group_id=1, user_id=1)     │
    │ points = 5000               │
    ├─────────────────────────────┤
    │ (group_id=1, user_id=2)     │
    │ points = 3000               │
    ├─────────────────────────────┤
    │ (group_id=1, user_id=3)     │
    │ points = 2000               │
    │                             │
    │ SUM = 10,000 ✅ MATCH       │
    └─────────────────────────────┘
```

---

## 🚀 Deployment Flow

```
┌─────────────────────────────────────────┐
│ 1. Desarrollador: git commit            │
│    - Migration file                     │
│    - Job file                           │
│    - Controller                         │
│    - Routes                             │
│    - Blade view                         │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│ 2. git push origin main                 │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│ 3. GitHub Actions: CriticalViewsTest    │
│    - Run 24 tests                       │
│    - Check deployment requirements      │
└────────────────────┬────────────────────┘
                     │
      ┌──────────────┴──────────────┐
      │ Tests PASSED ✅             │
      │                             │
      ▼                             ▼
 Production                   Stay in develop
 Deploy                       Fix issues
      │
      ▼
┌─────────────────────────────────────────┐
│ 4. SSH to production server             │
│    - git pull origin main               │
│    - php artisan migrate                │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│ 5. Scheduler picks up new Job           │
│    - Next hour: UpdateGroupTotalPointsJob    │
│    - Runs hourly: */1 * * * *           │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│ 6. Route available                      │
│    - GET /groups/{id}/summary lives!    │
│    - Admin can access it                │
└─────────────────────────────────────────┘
```

---

## 💾 Database Indexes Diagram

```
┌─────────────────────────────────────────┐
│ Índices para Performance Óptima         │
└─────────────────────────────────────────┘

SIN ÍNDICES (❌ LENTO):
Query: SUM(group_user.points WHERE group_id = 1)
╔════════════════════════════════════╗
║ Tabla: group_user (1M registros)   ║
║ ┌────────────┐┌────────────┐      ║
║ │ group_id   ││ points     ║      ║
║ │ 1  ↑ SCAN ││ 5000       ║      ║
║ │ 1  │ TODO ││ 3000       ║      ║
║ │ 2  │ ESTO ││ 2000       ║      ║
║ │ 3  │      ││ 1000       ║      ║
║ │ 1  │      ││ 4000       ║      ║
║ └────────────┘└────────────┘      ║
║ Tiempo: ~500ms ⚠️                 ║
╚════════════════════════════════════╝


CON ÍNDICES (✅ RÁPIDO):
Query: SUM(group_user.points WHERE group_id = 1)
╔════════════════════════════════════╗
║ Índice: (group_id, points)         ║
║ ┌────────────────────────────┐    ║
║ │ group_id=1 → [5000,3000..] │ ✓ rápido
║ │ group_id=2 → [...]         │   acceso
║ │ group_id=3 → [...]         │   directo
║ └────────────────────────────┘    ║
║ Tiempo: ~20ms ✅                  ║
╚════════════════════════════════════╝

Índices creados:
- groups (total_points)              ← para ORDER BY
- group_user (group_id, points)      ← para SUM() rápido
```

---

## 🧪 Testing Architecture

```
┌─────────────────────────────────────────┐
│ Test Layers for Group Summary           │
└─────────────────────────────────────────┘

┌─ UNIT TESTS ──────────────────────────┐
│                                       │
│ ✅ UpdateGroupTotalPointsJob::test    │
│    - Verifica SUM correcto            │
│    - Actualiza columna                │
│    - Maneja errores                   │
│                                       │
│ ✅ GroupSummaryController::test       │
│    - getTopMembers() retorna 10       │
│    - getMemberStats() calcula bien    │
│    - getPointsDistribution() forma    │
└───────────────────┬───────────────────┘
                    │
┌───────────────────┴───────────────────┐
│ FEATURE TESTS                         │
│                                       │
│ ✅ GET /groups/{id}/summary          │
│    - Creador puede ver (200)          │
│    - Otro no puede (403)              │
│    - Datos muestran correctamente     │
│    - View tiene variables necesarias  │
└───────────────────┬───────────────────┘
                    │
┌───────────────────┴───────────────────┐
│ INTEGRATION TESTS                     │
│                                       │
│ ✅ Job + Controller                  │
│    - Job corre → Controller ve datos  │
│    - Usuario ve datos actualizados    │
│    - Permisos funcionan               │
└───────────────────┬───────────────────┘
                    │
┌───────────────────┴───────────────────┐
│ E2E / BROWSER TESTS (Opcional)        │
│                                       │
│ ✅ Selenium/Playwright               │
│    - Usuario accede página            │
│    - Haga clic en botones             │
│    - Datos visibles en UI             │
└─────────────────────────────────────────┘
```

---

## 📈 Performance Timeline

```
Request: GET /groups/1/summary

Time: 0ms     ┌─── START ───┐
              │              
Time: 10ms    │ Route dispatch
              │ Middleware auth
              │ Gate authorization
              │
Time: 20ms    ├─── DB QUERIES ───┐
              │                   │
Time: 50ms    │ ├─ SUM(points)    └─ 30ms
              │ ├─ Top 10 members └─ 15ms
              │ ├─ Distribution   └─ 10ms
              │ ├─ Member stats   └─ 8ms
              │ │
Time: 80ms    ├─── CONTROLLER LOGIC ───┐
              │ ├─ Calculate stats      └─ 5ms
              │ │
Time: 85ms    ├─── VIEW RENDERING ───┐
              │ ├─ Blade compile      └─ 30ms
              │ ├─ Color values       (inline)
              │ │
Time: 115ms   ├─── RESPONSE ───┐
              │ ├─ JSON/HTML    └─ 5ms
              │ │
Time: 120ms   └─── FINISH ───┘

TTFB (Time to First Byte): ~120ms ✅
With Cache (1 hour): ~50ms ⚡
```

---

## 🔌 Integration Points

```
┌─────────────────────────────────────────┐
│ Cómo se integra con el sistema existente│
└─────────────────────────────────────────┘

Group Summary                  Existing Systems
        │
        ├─→ Groups Model        (exists ✓)
        │   └─→ relationships
        │
        ├─→ Users Model         (exists ✓)
        │   └─→ getAvatarUrl()
        │
        ├─→ Question Model      (exists ✓)
        │   └─→ answers relation
        │
        ├─→ Answer Model        (exists ✓)
        │   └─→ points_earned
        │
        ├─→ GroupPolicy         (new ✓)
        │   └─→ viewSummary()
        │
        ├─→ Scheduler           (exists ✓)
        │   └─→ schedule() method
        │
        └─→ Layout (x-app-layout)  (exists ✓)
            └─→ dark mode support

Synchronization with:
✅ Phase 4 Points Cache (group_user.points)
✅ Dark Mode System
✅ Authorization System
✅ Responsive Design
```

---

**Última Actualización:** 22 de Abril, 2026
