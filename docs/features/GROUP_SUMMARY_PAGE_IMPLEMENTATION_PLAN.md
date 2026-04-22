# 📊 Group Summary Page & Total Points Implementation Plan

**Date Created:** April 22, 2026  
**Priority:** MEDIUM  
**Complexity:** MEDIUM  
**Estimated Time:** 8-12 hours  

---

## 🎯 Objetivo General

Crear un sistema de **seguimiento centralizado de puntos por grupo** y una **página de resumen administrativo** que muestre métricas, estadísticas e información detallada del grupo, accesible por administradores y creadores del grupo.

---

## 📋 Requerimientos

### Funcionales

1. ✅ Agregar columna `total_points` a tabla `groups` (caché de suma)
2. ✅ Job horario `UpdateGroupTotalPointsJob` que sincroniza `group_user.points`
3. ✅ Ruta GET `/groups/{id}/summary` - Vista de resumen
4. ✅ Mostrar estadísticas en tiempo real (con datos cacheados)
5. ✅ Historial de cambios de puntos (auditoría - opcional fase 2)
6. ✅ Soporte para logros desbloqueados (plantilla para fase 2)

### No Funcionales

- Queries optimizadas (índices en `group_user`)
- Caché de 1 hora para no recalcular constantemente
- Responsive design (mobile-first)
- Dark mode support (como el resto de la app)
- Performance < 200ms para carga inicial

---

## 🗄️ Cambios de Base de Datos

### Migration: Agregar columna `total_points` a `groups`

**Archivo:** `database/migrations/2026_04_22_000000_add_total_points_to_groups_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // Columna de caché para sumatoria de puntos
            $table->bigInteger('total_points')
                ->default(0)
                ->after('expires_at')
                ->comment('Sumatoria de todos los group_user.points - se actualiza cada hora');
            
            // Timestamp de última actualización
            $table->timestamp('total_points_updated_at')
                ->nullable()
                ->after('total_points')
                ->comment('Última vez que se actualizó total_points');
            
            // Índice para queries rápidas
            $table->index('total_points');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['total_points']);
            $table->dropColumn(['total_points', 'total_points_updated_at']);
        });
    }
};
```

### Modificación: Índices en `group_user`

**Agregar a una nueva migration:**

```php
Schema::table('group_user', function (Blueprint $table) {
    // Índice para queries de suma rápida
    $table->index(['group_id', 'points']);
});
```

---

## 🤖 Job para Actualización Horaria

### Archivo: `app/Jobs/UpdateGroupTotalPointsJob.php`

**Purpose:** Ejecutarse cada hora (scheduler) para recalcular el total de puntos por grupo

```php
<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateGroupTotalPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos

    public function handle(): void
    {
        Log::info('Starting UpdateGroupTotalPointsJob');

        $updatedCount = 0;
        $errorCount = 0;

        // Usar chunking para no cargar todos los grupos en memoria
        Group::chunk(100, function ($groups) use (&$updatedCount, &$errorCount) {
            foreach ($groups as $group) {
                try {
                    // Calcular suma de puntos del grupo
                    $totalPoints = DB::table('group_user')
                        ->where('group_id', $group->id)
                        ->sum('points');

                    // Actualizar grupo
                    $group->update([
                        'total_points' => $totalPoints,
                        'total_points_updated_at' => now(),
                    ]);

                    $updatedCount++;

                    Log::debug('Group total points updated', [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'total_points' => $totalPoints,
                    ]);

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Error updating group total points', [
                        'group_id' => $group->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('UpdateGroupTotalPointsJob completed', [
            'updated_count' => $updatedCount,
            'error_count' => $errorCount,
        ]);
    }
}
```

### Registrar en Scheduler

**Archivo:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Ejecutar cada hora
    $schedule->job(new UpdateGroupTotalPointsJob())
        ->hourly()
        ->name('update-group-total-points')
        ->onOneServer(); // Ejecutar solo en un servidor si hay múltiples

    // O ejecutar a horas específicas (cada 6 horas)
    $schedule->job(new UpdateGroupTotalPointsJob())
        ->cron('0 */6 * * *') // 00:00, 06:00, 12:00, 18:00
        ->name('update-group-total-points-batch');
}
```

---

## 🔧 Backend: Controller & Routes

### Controller: `app/Http/Controllers/GroupSummaryController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class GroupSummaryController extends Controller
{
    /**
     * Mostrar página de resumen del grupo
     * Solo para creador del grupo o administradores
     */
    public function show(Group $group)
    {
        // Autorización
        Gate::authorize('view-summary', $group);

        // Obtener estadísticas
        $stats = [
            'total_points' => $group->total_points,
            'member_count' => $group->users()->count(),
            'question_count' => $group->questions()->count(),
            'answered_count' => $group->answers()->whereNotNull('is_correct')->count(),
            'message_count' => $group->chatMessages()->count(),
            'top_members' => $this->getTopMembers($group, 10),
            'points_distribution' => $this->getPointsDistribution($group),
            'member_stats' => $this->getMemberStats($group),
            'recent_activity' => $this->getRecentActivity($group, 20),
        ];

        return view('groups.summary', compact('group', 'stats'));
    }

    /**
     * Obtener top 10 miembros por puntos
     */
    private function getTopMembers(Group $group, int $limit = 10)
    {
        return $group->users()
            ->select('users.*', 'group_user.points')
            ->orderByDesc('group_user.points')
            ->limit($limit)
            ->get();
    }

    /**
     * Distribución de puntos (para gráfico)
     */
    private function getPointsDistribution(Group $group)
    {
        return DB::table('group_user')
            ->where('group_id', $group->id)
            ->select(
                DB::raw('CASE 
                    WHEN points = 0 THEN "Sin puntos"
                    WHEN points < 1000 THEN "0-1000"
                    WHEN points < 5000 THEN "1000-5000"
                    WHEN points < 10000 THEN "5000-10000"
                    ELSE "10000+"
                 END as range'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('range')
            ->get()
            ->pluck('count', 'range');
    }

    /**
     * Estadísticas por miembro
     */
    private function getMemberStats(Group $group)
    {
        return [
            'avg_points' => $group->users()->avg('group_user.points'),
            'max_points' => $group->users()->max('group_user.points'),
            'min_points' => $group->users()->min('group_user.points'),
            'median_points' => $this->getMedianPoints($group),
        ];
    }

    /**
     * Calcular mediana de puntos
     */
    private function getMedianPoints(Group $group)
    {
        $count = $group->users()->count();
        $points = $group->users()
            ->select('group_user.points')
            ->orderBy('group_user.points')
            ->pluck('points')
            ->toArray();

        if (empty($points)) return 0;

        $mid = floor($count / 2);
        if ($count % 2 === 0) {
            return ($points[$mid - 1] + $points[$mid]) / 2;
        }
        return $points[$mid];
    }

    /**
     * Actividad reciente (respuestas, chats)
     */
    private function getRecentActivity(Group $group, int $limit = 20)
    {
        $answers = $group->answers()
            ->with('user:id,name,avatar')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($a) => [
                'type' => 'answer',
                'user' => $a->user,
                'created_at' => $a->created_at,
                'description' => $a->is_correct ? 'Respondió correctamente' : 'Respondió (incorrecto)',
                'points' => $a->points_earned,
            ]);

        return $answers->sortByDesc('created_at')->values()->take($limit);
    }
}
```

### Routes: `routes/web.php`

```php
// Group summary (admin/creator only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/groups/{group}/summary', [GroupSummaryController::class, 'show'])
        ->name('groups.summary');
});
```

### Policy: `app/Policies/GroupPolicy.php`

```php
public function viewSummary(User $user, Group $group): bool
{
    // Solo creador del grupo o administradores
    return $user->id === $group->created_by || $user->is_admin;
}
```

---

## 🎨 Frontend: Vista de Resumen

### Blade Template: `resources/views/groups/summary.blade.php`

```blade
<x-app-layout>
    @section('navigation-title', $group->name . ' - Resumen')

    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $accentColor = '#00deb0';
    @endphp

    <div class="min-h-screen p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Header con botones -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; margin: 0;">{{ $group->name }}</h1>
                <p style="color: {{ $isDark ? '#b0b0b0' : '#999999' }}; margin: 8px 0 0 0;">
                    Resumen del Grupo
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('groups.edit', $group) }}" 
                   style="padding: 10px 16px; background: {{ $accentColor }}; color: #000; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    ✏️ Editar Grupo
                </a>
                <a href="{{ route('groups.show', $group) }}" 
                   style="padding: 10px 16px; background: {{ $isDark ? '#1a524e' : '#e5f3f0' }}; color: {{ $accentColor }}; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid {{ $accentColor }};">
                    ← Volver al Grupo
                </a>
            </div>
        </div>

        <!-- Estadísticas principales (4 columnas) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <!-- Total de Puntos -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid {{ $accentColor }};">
                <div style="font-size: 12px; color: #b0b0b0; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    💰 Total de Puntos
                </div>
                <div style="font-size: 32px; font-weight: 700; color: {{ $accentColor }};">
                    {{ number_format($stats['total_points'], 0, ',', '.') }}
                </div>
                <div style="font-size: 12px; color: #999999; margin-top: 8px;">
                    Actualizado: {{ $group->total_points_updated_at?->diffForHumans() ?? 'Nunca' }}
                </div>
            </div>

            <!-- Integrantes -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #ff6b6b;">
                <div style="font-size: 12px; color: #b0b0b0; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    👥 Integrantes
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #ff6b6b;">
                    {{ $stats['member_count'] }}
                </div>
                <div style="font-size: 12px; color: #999999; margin-top: 8px;">
                    Promedio: {{ number_format($stats['member_stats']['avg_points'], 0) }} pts
                </div>
            </div>

            <!-- Preguntas -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #ffd93d;">
                <div style="font-size: 12px; color: #b0b0b0; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    ❓ Preguntas
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #ffd93d;">
                    {{ $stats['question_count'] }}
                </div>
                <div style="font-size: 12px; color: #999999; margin-top: 8px;">
                    Respondidas: {{ $stats['answered_count'] }}
                </div>
            </div>

            <!-- Mensajes Chat -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #17b796;">
                <div style="font-size: 12px; color: #b0b0b0; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    💬 Mensajes
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #17b796;">
                    {{ $stats['message_count'] }}
                </div>
                <div style="font-size: 12px; color: #999999; margin-top: 8px;">
                    Conversaciones activas
                </div>
            </div>
        </div>

        <div style="display: grid; grid-cols-1 lg:grid-cols-2 gap-24;">
            <!-- Columna izquierda -->
            <div>
                <!-- Top Miembros -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                        🏆 Top 10 Miembros
                    </h2>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach($stats['top_members'] as $index => $member)
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $isDark ? '#0f3d3a' : '#f9f9f9' }}; border-radius: 8px;">
                            <div style="font-weight: 700; color: {{ $accentColor }}; min-width: 24px;">
                                #{{ $index + 1 }}
                            </div>
                            <img src="{{ $member->getAvatarUrl('small') }}" alt="{{ $member->name }}" 
                                 style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600;">{{ $member->name }}</div>
                                <div style="font-size: 12px; color: #999999;">{{ $member->email }}</div>
                            </div>
                            <div style="font-weight: 700; color: {{ $accentColor }};">
                                {{ number_format($member->points, 0) }} pts
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Información del Grupo -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0;">
                        ℹ️ Información del Grupo
                    </h2>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Código:</span>
                            <span style="font-weight: 600; color: {{ $accentColor }};">{{ $group->code }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Creado por:</span>
                            <span style="font-weight: 600;">{{ $group->creator->name }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Fecha de Creación:</span>
                            <span style="font-weight: 600;">{{ $group->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Categoría:</span>
                            <span style="font-weight: 600;">{{ ucfirst($group->category ?? 'estándar') }}</span>
                        </div>
                        @if($group->expires_at)
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Expira el:</span>
                            <span style="font-weight: 600; color: #ff6b6b;">{{ $group->expires_at->format('d/m/Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Columna derecha -->
            <div>
                <!-- Estadísticas de Distribución -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0;">
                        📊 Distribución de Puntos
                    </h2>
                    @if($stats['points_distribution']->isNotEmpty())
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach($stats['points_distribution'] as $range => $count)
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-size: 12px;">{{ $range }}</span>
                                <span style="font-weight: 600; color: {{ $accentColor }};">{{ $count }} miembros</span>
                            </div>
                            <div style="background: {{ $isDark ? '#0f3d3a' : '#e0e0e0' }}; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: {{ $accentColor }}; height: 100%; width: {{ ($count / $stats['member_count'] * 100) }}%;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p style="color: #999999; text-align: center; padding: 20px 0;">
                        No hay datos aún
                    </p>
                    @endif
                </div>

                <!-- Resumen Estadístico -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0;">
                        📈 Resumen Estadístico
                    </h2>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Máximo:</span>
                            <span style="font-weight: 600; color: #00d084;">{{ number_format($stats['member_stats']['max_points'], 0) }} pts</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Promedio:</span>
                            <span style="font-weight: 600; color: {{ $accentColor }};">{{ number_format($stats['member_stats']['avg_points'], 0) }} pts</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#e0e0e0' }}; padding-bottom: 8px;">
                            <span>Mediana:</span>
                            <span style="font-weight: 600; color: #17b796;">{{ number_format($stats['member_stats']['median_points'], 0) }} pts</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                            <span>Mínimo:</span>
                            <span style="font-weight: 600; color: #ff6b6b;">{{ number_format($stats['member_stats']['min_points'], 0) }} pts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas Features (comentado) -->
        {{-- 
        <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; margin-top: 24px;">
            <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0;">
                🎖️ Logros Desbloqueados
            </h2>
            <!-- Pronto: Lista de logros del grupo -->
        </div>
        --}}
    </div>

    <x-layout.bottom-navigation active-item="grupo" />
</x-app-layout>
```

---

## 📑 File Checklist

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/migrations/2026_04_22_000000_add_total_points_to_groups_table.php` | Migration | Agregar columnas a tabla groups |
| `app/Jobs/UpdateGroupTotalPointsJob.php` | Job | Job que actualiza total_points cada hora |
| `app/Http/Controllers/GroupSummaryController.php` | Controller | Controlador de resumen |
| `app/Policies/GroupPolicy.php` | Policy | Política de autorización (actualizar) |
| `resources/views/groups/summary.blade.php` | View | Vista de resumen del grupo |
| `routes/web.php` | Route | Ruta `/groups/{id}/summary` |

---

## 🚀 Plan de Implementación Fase 1

### Semana 1: Backend

**Día 1-2: Base de Datos**
- [ ] Crear migration para agregar `total_points` y `total_points_updated_at`
- [ ] Actualizar modelo `Group.php` con nuevos fillable y casts
- [ ] Ejecutar migration

**Día 2-3: Job**
- [ ] Crear `UpdateGroupTotalPointsJob`
- [ ] Registrar en scheduler (hourly)
- [ ] Pruebas manuales con `php artisan schedule:work`

**Día 3-4: Controller & Routes**
- [ ] Crear `GroupSummaryController` con método `show()`
- [ ] Implementar métodos de estadísticas
- [ ] Agregar rutas en `web.php`
- [ ] Actualizar `GroupPolicy` con `viewSummary()`

### Semana 2: Frontend

**Día 5-6: Blade Template**
- [ ] Crear `groups/summary.blade.php`
- [ ] Agregar responsive grid layout
- [ ] Dark mode support
- [ ] Mobile optimization

**Día 6-7: Testing & Polish**
- [ ] Tests unitarios para Controller
- [ ] Tests de caché (Job)
- [ ] Tests de autorización (Policy)
- [ ] Performance testing
- [ ] Cross-browser testing

**Día 7: Deploy**
- [ ] Commit y push a main
- [ ] Ejecutar migraciones en producción
- [ ] Verificar Job en scheduler
- [ ] Monitoreo en vivo

---

## ✨ Features Sugeridas (Fase 2+)

### 🎖️ Sistema de Logros (Phase 2)

**Logros por hito:**
- 🥇 "Primer Sangre" - Primer miembro llega a 100 puntos
- 🏆 "Legión" - Grupo alcanza 100k puntos totales
- 🔥 "En Fuego" - Miembro tiene racha de 7 días respondiendo
- 👑 "Dominio" - Miembro lidera por más de 30 días
- 🌟 "Veterano" - Miembro en grupo por 6+ meses
- 💎 "Élite" - Grupo tiene 10+ miembros activos

**UI:**
- Sección "Logros Desbloqueados" en summary
- Badge visual cuando se desbloquea un logro
- Notificación push al grupo
- Historial de logros

### 📊 Tablero Avanzado (Phase 2)

- **Gráficas interactivas:** Chart.js para tendencias de puntos
- **Timeline:** Eventos del grupo en order cronológico
- **Actividad diaria:** Heatmap de actividad por día/hora
- **Comparativas:** Grupo vs otros grupos similares
- **Exportar datos:** CSV/PDF con estadísticas

### 🏅 Leaderboard Avanzado (Phase 2)

- **Filtros:** Por período (7d, 30d, all-time)
- **Rankings:** Por actividad, respuestas correctas, racha
- **Badges:** Mostrar logros al lado del nombre
- **Tendencias:** ↑↓ Cambios en ranking desde hace 7 días

### 🔔 Sistema de Notificaciones (Phase 2)

- Alertas cuando miembro es desplazado del top 3
- Notificación cuando alguien desbloquea logro
- Recordatorio semanal de estadísticas
- Alert cuando grupo alcanza hito (10k, 50k, 100k puntos)

### 📁 Gestión Avanzada (Phase 3)

- **Roles:** Admin del grupo, Moderador, Miembro
- **Auditoría:** Log de cambios (quién modificó qué)
- **Suspensión:** Poder remover miembros temporalmente
- **Backup:** Descargar historial de datos del grupo
- **Archivado:** Archivar grupos completados

### 🎓 Análisis Educativo (Phase 3)

- Tasa de éxito por pregunta
- Predicción de puntos futuros (ML)
- Identificar preguntas problemáticas
- Recomendaciones de contenido
- A/B testing de preguntas

---

## 🔒 Seguridad & Performance

### Optimizaciones

```php
// Cache de 1 hora
Cache::remember("group.{$group->id}.summary", 3600, function () {
    return $this->calculateSummary($group);
});
```

### Índices de BD

```sql
-- Agregar índices para queries rápidas
ALTER TABLE group_user ADD INDEX idx_group_points (group_id, points);
ALTER TABLE groups ADD INDEX idx_total_points (total_points);
```

### Rate Limiting

```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/groups/{group}/summary', ...);
});
```

---

## 📊 Testing

### Unit Tests

```php
// tests/Unit/Jobs/UpdateGroupTotalPointsJobTest.php
public function test_job_calculates_total_points_correctly()
{
    $group = Group::factory()->create();
    $group->users()->attach([
        1 => ['points' => 100],
        2 => ['points' => 200],
    ]);

    UpdateGroupTotalPointsJob::dispatch();

    $group->refresh();
    $this->assertEquals(300, $group->total_points);
}
```

### Feature Tests

```php
// tests/Feature/GroupSummaryTest.php
public function test_only_creator_can_view_summary()
{
    $creator = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $creator->id]);

    $this->actingAs($creator)
        ->get("/groups/{$group->id}/summary")
        ->assertStatus(200);

    $other = User::factory()->create();
    $this->actingAs($other)
        ->get("/groups/{$group->id}/summary")
        ->assertStatus(403);
}
```

---

## 📱 Responsive Design

| Breakpoint | Layout |
|-----------|--------|
| Mobile (< 640px) | 1 columna, cards apiladas |
| Tablet (640-1024px) | 2 columnas |
| Desktop (> 1024px) | 2 columnas con grid de stats |

---

## 🎯 Métricas de Éxito

- ✅ Total de puntos se actualiza correctamente cada hora
- ✅ Página carga en < 200ms
- ✅ Acceso limitado solo al creador del grupo
- ✅ 100% test coverage en Controller
- ✅ Soporte para 50+ miembros sin lag
- ✅ Funciona correctamente en mobile

---

## 📝 Comandos Útiles

```bash
# Crear migration
php artisan make:migration add_total_points_to_groups_table

# Crear Job
php artisan make:job UpdateGroupTotalPointsJob

# Crear Controller
php artisan make:controller GroupSummaryController

# Crear Policy
php artisan make:policy GroupPolicy --model=Group

# Ejecutar migraciones
php artisan migrate

# Probar scheduler
php artisan schedule:work

# Ejecutar Job manualmente
php artisan tinker
>>> App\Jobs\UpdateGroupTotalPointsJob::dispatch();

# Ver logs
tail -f storage/logs/laravel.log
```

---

## 📚 Referencias

- [Laravel Scheduling](https://laravel.com/docs/11.x/scheduling)
- [Laravel Policies](https://laravel.com/docs/11.x/authorization#policies)
- [Database Indexes](https://laravel.com/docs/11.x/migrations#indexes)
- [Caching](https://laravel.com/docs/11.x/cache)

---

## 🔄 Notas de Implementación

### Paso 1: Migration & Model

```bash
php artisan make:migration add_total_points_to_groups_table
```

### Paso 2: Sync Inicial

Después de migrar, ejecutar un comando para calcular totales iniciales:

```php
// Comando: php artisan group:sync-total-points
Group::all()->each(function ($group) {
    $group->update([
        'total_points' => $group->users()->sum('group_user.points')
    ]);
});
```

### Paso 3: Validación

Verificar que el total es correcto:

```php
$group = Group::find(1);
$calculated = $group->users()->sum('group_user.points');
$stored = $group->total_points;

assert($calculated === $stored, "Mismatch: {$calculated} vs {$stored}");
```

---

**Última Actualización:** 22 de Abril, 2026
