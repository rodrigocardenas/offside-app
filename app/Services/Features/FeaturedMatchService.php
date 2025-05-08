<?php

namespace App\Services\Features;

use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class FeaturedMatchService
{
    /**
     * Lista de clásicos conocidos
     * Formato: [['home' => 'Equipo Local', 'away' => 'Equipo Visitante']]
     */
    protected $classicMatches = [
        ['home' => 'Barcelona', 'away' => 'Real Madrid'],
        ['home' => 'Barcelona', 'away' => 'Espanyol'],
        ['home' => 'Real Madrid', 'away' => 'Atletico Madrid'],
        ['home' => 'Barcelona', 'away' => 'Atletico Madrid'],
        ['home' => 'Real Madrid', 'away' => 'Sevilla'],
        ['home' => 'Barcelona', 'away' => 'Sevilla'],
        ['home' => 'Atletico Madrid', 'away' => 'Valencia'],
        ['home' => 'Barcelona', 'away' => 'Valencia'],
        ['home' => 'Real Madrid', 'away' => 'Barcelona'],
        ['home' => 'Atletico Madrid', 'away' => 'Sevilla'],
        ['home' => 'Athletic Club', 'away' => 'Real Sociedad'],
        ['home' => 'Sevilla', 'away' => 'Real Betis'],
        ['home' => 'Valencia', 'away' => 'Villarreal'],
        // premier league:
        ['home' => 'Manchester City', 'away' => 'Liverpool'],
        ['home' => 'Manchester City', 'away' => 'Manchester United'],
        ['home' => 'Manchester City', 'away' => 'Chelsea'],
        ['home' => 'Manchester City', 'away' => 'Arsenal'],
        ['home' => 'Manchester United', 'away' => 'Liverpool'],
        ['home' => 'Manchester United', 'away' => 'Manchester City'],
        ['home' => 'Manchester United', 'away' => 'Chelsea'],
        ['home' => 'Manchester United', 'away' => 'Arsenal'],
        ['home' => 'Liverpool', 'away' => 'Manchester City'],
        ['home' => 'Liverpool', 'away' => 'Manchester United'],
        ['home' => 'Liverpool', 'away' => 'Chelsea'],
        ['home' => 'Liverpool', 'away' => 'Arsenal'],
        ['home' => 'Chelsea', 'away' => 'Manchester City'],
        ['home' => 'Chelsea', 'away' => 'Manchester United'],
        ['home' => 'Chelsea', 'away' => 'Liverpool'],
        ['home' => 'Chelsea', 'away' => 'Arsenal'],
        ['home' => 'Arsenal', 'away' => 'Manchester City'],
        ['home' => 'Arsenal', 'away' => 'Manchester United'],
        ['home' => 'Arsenal', 'away' => 'Liverpool'],
        ['home' => 'Arsenal', 'away' => 'Chelsea'],
    ];

    /**
     * Actualiza los partidos destacados basados en varios criterios
     */
    public function updateFeaturedMatches()
    {
        try {
            // Primero, obtener todos los partidos futuros
            $upcomingMatches = FootballMatch::where('date', '>=', now())
                // ->where('date', '<=', now()->addDays(7)) // Solo mirar la próxima semana
                ->with(['homeTeam', 'awayTeam'])
                ->get();

            foreach ($upcomingMatches as $match) {
                $isFeatured = $this->shouldBeFeatured($match);
                
                // Actualizar solo si el estado ha cambiado
                if ($match->is_featured !== $isFeatured) {
                    $match->update(['is_featured' => $isFeatured]);
                }
            }

            Log::info('Partidos destacados actualizados correctamente');
            return true;
        } catch (\Exception $e) {
            Log::error('Error al actualizar partidos destacados: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Determina si un partido debe ser destacado basado en varios criterios
     */
    protected function shouldBeFeatured(FootballMatch $match): bool
    {
        // 1. Verificar si es un clásico o derby
        if ($this->isClassicMatch($match)) {
            return true;
        }

        // 2. Verificar si alguno de los equipos está en los primeros puestos
        if ($this->isTopTeamMatch($match)) {
            return true;
        }

        // 3. Verificar si es un partido de fases finales o eliminatorias
        if ($this->isKnockoutStage($match)) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si el partido es un clásico o derby
     */
    protected function isClassicMatch(FootballMatch $match): bool
    {
        if (!$match->home_team || !$match->away_team) {
            return false;
        }

        $homeTeamName = $match->home_team;
        $awayTeamName = $match->away_team;

        foreach ($this->classicMatches as $classic) {
            if (($homeTeamName === $classic['home'] && $awayTeamName === $classic['away']) ||
                ($homeTeamName === $classic['away'] && $awayTeamName === $classic['home'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si alguno de los equipos está en los primeros puestos de su liga
     * 
     * Por ahora, consideraremos equipos destacados basados en su reputación
     * hasta que se implemente un sistema de posiciones de liga
     */
    protected function isTopTeamMatch(FootballMatch $match): bool
    {
        if (!$match->home_team || !$match->away_team) {
            return false;
        }

        // Lista de equipos considerados "top" por su reputación
        $topTeams = [
            'Real Madrid',
            'Barcelona',
            'Atletico de Madrid',
            // 'Sevilla',
            // 'Valencia',
            // 'Villarreal',
            // 'Athletic Club',
            // 'Real Sociedad',
            // 'Real Betis',
            'Manchester City',
            'Liverpool',
            'Chelsea',
            'Manchester United',
            'Arsenal',
            // 'Tottenham',
            'Bayern Munich',
            'Borussia Dortmund',
            // 'RB Leipzig',
            // 'Bayer Leverkusen',
            'PSG',
            // 'Marseille',
            // 'Lyon',
            'Juventus',
            'Inter',
            'Milan',
            // 'Napoli',
            // 'Roma',
            // 'Ajax',
            // 'Porto',
            // 'Benfica',
            // 'PSV',
        ];

        // Verificar si alguno de los equipos está en la lista de equipos destacados
        return in_array($match->home_team, $topTeams) || 
               in_array($match->away_team, $topTeams);
    }

    /**
     * Verifica si es un partido de fases finales o eliminatorias
     */
    protected function isKnockoutStage(FootballMatch $match): bool
    {
        if (empty($match->stage) && empty($match->group)) {
            return false;
        }

        $knockoutStages = [
            'QUARTER_FINALS',
            'SEMI_FINALS',
            'FINAL',
            // 'ROUND_OF_16',
            // 'ROUND_OF_32',
            // 'LAST_32',
            // 'LAST_64',
            'QUARTER_FINAL',
            'SEMI_FINAL',
            'FINAL',
            'ELIMINATORIAS',
            'PLAYOFFS',
            'OCTAVOS',
            'CUARTOS',
            'SEMIFINALES',
            'FINALES'
        ];

        $stage = !empty($match->stage) ? strtoupper($match->stage) : '';
        $group = !empty($match->group) ? strtoupper($match->group) : '';

        return in_array($stage, $knockoutStages) || 
               in_array($group, $knockoutStages) ||
               str_contains($stage, 'FINAL') ||
               str_contains($group, 'FINAL');
    }
}
