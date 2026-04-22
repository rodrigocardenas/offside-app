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
                ->join('questions', 'answers.question_id', '=', 'questions.id')
                ->where('questions.group_id', $group->id)
                ->whereNotNull('answers.is_correct')
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
                 END as `category`'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('CASE 
                    WHEN points = 0 THEN "Sin puntos"
                    WHEN points < 1000 THEN "0-1k"
                    WHEN points < 5000 THEN "1k-5k"
                    WHEN points < 10000 THEN "5k-10k"
                    ELSE "10k+"
                 END'))
            ->get();

        // Convertir a array con las claves correctas
        return $distribution->pluck('count', 'category')->toArray();
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
