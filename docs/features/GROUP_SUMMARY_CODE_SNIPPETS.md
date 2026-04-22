# 💻 Group Summary: Code Snippets Ready to Use

**Descripción:** Fragmentos de código listos para copiar y pegar en tu proyecto

---

## 1️⃣ Migration: Agregar columnas

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
                ->comment('Sumatoria de todos los group_user.points');
            
            // Timestamp de última actualización
            $table->timestamp('total_points_updated_at')
                ->nullable()
                ->after('total_points')
                ->comment('Última actualización de total_points');
            
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

**Ejecutar:**
```bash
php artisan migrate
```

---

## 2️⃣ Model: Actualizar Group.php

**Agregar al modelo `app/Models/Group.php`:**

```php
// En la propiedad $fillable
protected $fillable = [
    'name',
    'code',
    'created_by',
    'competition_id',
    'category',
    'reward_or_penalty',
    'expires_at',
    'cover_image',
    'cover_cloudflare_id',
    'cover_provider',
    'total_points',              // ← Agregar
    'total_points_updated_at',   // ← Agregar
];

// En la propiedad $casts
protected $casts = [
    'expires_at' => 'datetime',
    'total_points_updated_at' => 'datetime',  // ← Agregar
];

// Agregar scope para obtener grupos por total de puntos
public function scopeOrderByTotalPoints($query, $direction = 'desc')
{
    return $query->orderBy('total_points', $direction);
}
```

---

## 3️⃣ Job: UpdateGroupTotalPointsJob

**Archivo:** `app/Jobs/UpdateGroupTotalPointsJob.php`

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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 Starting UpdateGroupTotalPointsJob');

        $updatedCount = 0;
        $errorCount = 0;
        $totalPointsChanged = 0;

        try {
            // Usar chunking para evitar cargar todo en memoria
            Group::chunk(100, function ($groups) use (&$updatedCount, &$errorCount, &$totalPointsChanged) {
                foreach ($groups as $group) {
                    try {
                        $oldTotalPoints = $group->total_points;

                        // Calcular suma de puntos del grupo
                        $newTotalPoints = DB::table('group_user')
                            ->where('group_id', $group->id)
                            ->sum('points');

                        // Actualizar grupo
                        $group->update([
                            'total_points' => $newTotalPoints,
                            'total_points_updated_at' => now(),
                        ]);

                        $updatedCount++;

                        // Detectar cambios importantes
                        if ($newTotalPoints !== $oldTotalPoints) {
                            $totalPointsChanged++;
                            Log::debug('Group total points changed', [
                                'group_id' => $group->id,
                                'group_name' => $group->name,
                                'old_total' => $oldTotalPoints,
                                'new_total' => $newTotalPoints,
                                'difference' => $newTotalPoints - $oldTotalPoints,
                            ]);
                        }

                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('❌ Error updating group total points', [
                            'group_id' => $group->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            Log::info('✅ UpdateGroupTotalPointsJob completed', [
                'updated_count' => $updatedCount,
                'changed_count' => $totalPointsChanged,
                'error_count' => $errorCount,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ UpdateGroupTotalPointsJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
```

**Crear Job:**
```bash
php artisan make:job UpdateGroupTotalPointsJob
```

---

## 4️⃣ Scheduler: Registrar en Kernel

**Archivo:** `app/Console/Kernel.php`

```php
<?php

namespace App\Console;

use App\Jobs\UpdateGroupTotalPointsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar cada hora
        $schedule->job(new UpdateGroupTotalPointsJob())
            ->hourly()
            ->name('update-group-total-points')
            ->onOneServer() // Solo ejecutar en un servidor si hay múltiples
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('✅ UpdateGroupTotalPointsJob ran successfully');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('❌ UpdateGroupTotalPointsJob failed');
            });

        // Alternativa: Ejecutar cada 6 horas específicamente
        // $schedule->job(new UpdateGroupTotalPointsJob())
        //     ->cron('0 */6 * * *') // 00:00, 06:00, 12:00, 18:00
        //     ->name('update-group-total-points-batch');
    }
}
```

**Probar scheduler:**
```bash
# Terminal 1: Ejecutar scheduler
php artisan schedule:work

# Terminal 2: En otra ventana, verificar logs
tail -f storage/logs/laravel.log | grep UpdateGroupTotalPoints
```

---

## 5️⃣ Controller: GroupSummaryController

**Archivo:** `app/Http/Controllers/GroupSummaryController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class GroupSummaryController extends Controller
{
    /**
     * Mostrar página de resumen del grupo
     */
    public function show(Group $group)
    {
        // Autorización: solo creador o admin
        Gate::authorize('viewSummary', $group);

        // Obtener todas las estadísticas
        $stats = [
            'total_points' => $group->total_points,
            'member_count' => $group->users()->count(),
            'question_count' => $group->questions()->count(),
            'answered_count' => DB::table('answers')
                ->where('group_id', $group->id)
                ->whereNotNull('is_correct')
                ->count(),
            'message_count' => $group->chatMessages()->count(),
            'top_members' => $this->getTopMembers($group, 10),
            'points_distribution' => $this->getPointsDistribution($group),
            'member_stats' => $this->getMemberStats($group),
        ];

        return view('groups.summary', compact('group', 'stats'));
    }

    /**
     * Obtener top N miembros por puntos
     */
    private function getTopMembers(Group $group, int $limit = 10)
    {
        return $group->users()
            ->select('users.*', 'group_user.points as total_points')
            ->orderByDesc('group_user.points')
            ->limit($limit)
            ->get();
    }

    /**
     * Distribución de puntos (para gráfico)
     */
    private function getPointsDistribution(Group $group)
    {
        $distribution = DB::table('group_user')
            ->where('group_id', $group->id)
            ->select(
                DB::raw('CASE 
                    WHEN points = 0 THEN "Sin puntos"
                    WHEN points < 1000 THEN "0-1k"
                    WHEN points < 5000 THEN "1k-5k"
                    WHEN points < 10000 THEN "5k-10k"
                    ELSE "10k+"
                 END as range'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('range')
            ->orderBy('range')
            ->get();

        // Convertir a array con las claves correctas
        return $distribution->pluck('count', 'range')->toArray();
    }

    /**
     * Estadísticas por miembro (avg, max, min, median)
     */
    private function getMemberStats(Group $group)
    {
        $userPoints = $group->users()
            ->select('group_user.points')
            ->orderBy('group_user.points')
            ->pluck('points')
            ->toArray();

        $count = count($userPoints);

        if ($count === 0) {
            return [
                'avg_points' => 0,
                'max_points' => 0,
                'min_points' => 0,
                'median_points' => 0,
                'std_dev_points' => 0,
            ];
        }

        // Calcular mediana
        $median = 0;
        if ($count % 2 === 0) {
            $median = ($userPoints[($count / 2) - 1] + $userPoints[$count / 2]) / 2;
        } else {
            $median = $userPoints[floor($count / 2)];
        }

        // Calcular desviación estándar
        $avg = array_sum($userPoints) / $count;
        $variance = array_sum(
            array_map(fn($x) => pow($x - $avg, 2), $userPoints)
        ) / $count;
        $stdDev = sqrt($variance);

        return [
            'avg_points' => (int)$avg,
            'max_points' => max($userPoints),
            'min_points' => min($userPoints),
            'median_points' => (int)$median,
            'std_dev_points' => (int)$stdDev,
        ];
    }
}
```

**Crear Controller:**
```bash
php artisan make:controller GroupSummaryController
```

---

## 6️⃣ Policy: Autorización

**Archivo:** `app/Policies/GroupPolicy.php` - Agregar método:

```php
public function viewSummary(User $user, Group $group): bool
{
    // Solo creador del grupo o administradores
    return $user->id === $group->created_by || $user->is_admin;
}
```

---

## 7️⃣ Routes: Registrar ruta

**Archivo:** `routes/web.php`

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Ruta de resumen del grupo (solo creador/admin)
    Route::get('/groups/{group}/summary', [GroupSummaryController::class, 'show'])
        ->name('groups.summary');
});
```

---

## 8️⃣ Test: Ejemplo de Test

**Archivo:** `tests/Feature/GroupSummaryTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que solo el creador puede ver el resumen
     */
    public function test_only_creator_can_view_summary(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        // Creador puede ver
        $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary")
            ->assertStatus(200)
            ->assertViewIs('groups.summary');

        // Otro usuario no puede ver
        $other = User::factory()->create();
        $this->actingAs($other)
            ->get("/groups/{$group->id}/summary")
            ->assertStatus(403);
    }

    /**
     * Test que el total de puntos es correcto
     */
    public function test_total_points_display_is_correct(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        // Agregar usuarios con puntos
        $group->users()->attach([
            User::factory()->create()->id => ['points' => 100],
            User::factory()->create()->id => ['points' => 200],
            User::factory()->create()->id => ['points' => 300],
        ]);

        // Actualizar el total
        $group->update(['total_points' => 600]);

        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");

        $response->assertStatus(200);
        $this->assertEquals(600, $response->viewData('stats')['total_points']);
    }

    /**
     * Test que el top 10 está ordenado correctamente
     */
    public function test_top_members_are_ordered_correctly(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        $users = User::factory(5)->create();
        
        $users->each(function ($user, $index) use ($group) {
            $points = (5 - $index) * 100; // 500, 400, 300, 200, 100
            $group->users()->attach($user->id, ['points' => $points]);
        });

        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");

        $topMembers = $response->viewData('stats')['top_members'];
        
        $this->assertEquals(500, $topMembers[0]->total_points);
        $this->assertEquals(400, $topMembers[1]->total_points);
        $this->assertEquals(100, $topMembers[4]->total_points);
    }
}
```

**Crear test:**
```bash
php artisan make:test GroupSummaryTest --feature
```

**Ejecutar tests:**
```bash
php artisan test tests/Feature/GroupSummaryTest.php
```

---

## 9️⃣ Blade View: Partial rápida

**Sección de estadísticas principales (para incluir en summary.blade.php):**

```blade
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
```

---

## 🔟 Artisan Commands: Útiles

```bash
# Crear migración
php artisan make:migration add_total_points_to_groups_table

# Ejecutar migraciones
php artisan migrate

# Crear Job
php artisan make:job UpdateGroupTotalPointsJob

# Crear Controller
php artisan make:controller GroupSummaryController

# Hacer Policy (si no existe)
php artisan make:policy GroupPolicy --model=Group

# Ejecutar scheduler en desarrollo
php artisan schedule:work

# Monitorear logs en tiempo real
tail -f storage/logs/laravel.log

# Ejecutar Job manualmente (para testing)
php artisan tinker
# Dentro de tinker:
dispatch(new App\Jobs\UpdateGroupTotalPointsJob());

# Ejecutar test
php artisan test tests/Feature/GroupSummaryTest.php

# Ejecutar específico
php artisan test tests/Feature/GroupSummaryTest.php --filter test_total_points_display_is_correct
```

---

## ✅ Checklist de Implementación

- [ ] Copiar contenido migration (1️⃣)
- [ ] Ejecutar `php artisan migrate`
- [ ] Actualizar Model Group.php (2️⃣)
- [ ] Copiar Job (3️⃣)
- [ ] Registrar en Kernel.php (4️⃣)
- [ ] Crear Controller (5️⃣)
- [ ] Copiar métodos del Controller (5️⃣)
- [ ] Agregar Policy (6️⃣)
- [ ] Agregar rutas (7️⃣)
- [ ] Crear tests (8️⃣)
- [ ] Crear Blade view (completa en plan detallado)
- [ ] Probar scheduler: `php artisan schedule:work`
- [ ] Testear autorización
- [ ] Deploy a producción

---

**¿Necesitas ayuda con algún snippet en específico? ¡Déjame saber!**
