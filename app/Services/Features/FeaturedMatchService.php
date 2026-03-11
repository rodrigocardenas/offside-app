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
        // 1. Verificar si ambos equipos son destacados (equipos grandes de su liga)
        if ($this->areBothTeamsFeatured($match)) {
            return true;
        }

        // 2. Verificar si es un clásico o derby
        if ($this->isClassicMatch($match)) {
            return true;
        }

        // 3. Verificar si alguno de los equipos está en los primeros puestos
        if ($this->isTopTeamMatch($match)) {
            return true;
        }

        // 4. Verificar si es un partido de fases finales o eliminatorias
        if ($this->isKnockoutStage($match)) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si ambos equipos son destacados (equipos grandes/importantes)
     * Busca en la tabla teams si ambos tienen is_featured = true
     */
    protected function areBothTeamsFeatured(FootballMatch $match): bool
    {
        if (!$match->home_team || !$match->away_team) {
            return false;
        }

        // Buscar ambos equipos por nombre en la tabla teams
        $homeTeam = Team::where('name', $match->home_team)
            ->orWhere('api_name', $match->home_team)
            ->first();

        $awayTeam = Team::where('name', $match->away_team)
            ->orWhere('api_name', $match->away_team)
            ->first();

        // Ambos equipos deben existir y estar marcados como destacados
        if ($homeTeam && $awayTeam && $homeTeam->is_featured && $awayTeam->is_featured) {
            Log::info('Match is featured: Both teams are featured', [
                'home_team' => $match->home_team,
                'away_team' => $match->away_team,
                'home_team_featured' => $homeTeam->is_featured,
                'away_team_featured' => $awayTeam->is_featured,
            ]);
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
     * Verifica si es un partido de semi final o final
     */
    protected function isKnockoutStage(FootballMatch $match): bool
    {
        if (empty($match->stage) && empty($match->group)) {
            return false;
        }

        $stage = !empty($match->stage) ? strtoupper($match->stage) : '';
        $group = !empty($match->group) ? strtoupper($match->group) : '';

        // Variaciones del nombre de semifinal
        $semiFinalsPatterns = [
            'SEMI_FINALS',
            'SEMI_FINAL',
            'SEMIFINALES',
            'SEMI-FINAL',
            'SEMI-FINALS',
            'SEMIS',
        ];

        // Variaciones del nombre de final
        $finalsPatterns = [
            'FINAL',
            'FINALES',
        ];

        // Verificar en stage
        foreach ($semiFinalsPatterns as $pattern) {
            if ($stage === $pattern || str_contains($stage, $pattern)) {
                Log::info('Match is featured: Semi-final detected', [
                    'home_team' => $match->home_team,
                    'away_team' => $match->away_team,
                    'stage' => $match->stage,
                ]);
                return true;
            }
        }

        foreach ($finalsPatterns as $pattern) {
            if ($stage === $pattern || str_contains($stage, $pattern)) {
                Log::info('Match is featured: Final detected', [
                    'home_team' => $match->home_team,
                    'away_team' => $match->away_team,
                    'stage' => $match->stage,
                ]);
                return true;
            }
        }

        // Verificar en group
        foreach ($semiFinalsPatterns as $pattern) {
            if ($group === $pattern || str_contains($group, $pattern)) {
                Log::info('Match is featured: Semi-final detected in group', [
                    'home_team' => $match->home_team,
                    'away_team' => $match->away_team,
                    'group' => $match->group,
                ]);
                return true;
            }
        }

        foreach ($finalsPatterns as $pattern) {
            if ($group === $pattern || str_contains($group, $pattern)) {
                Log::info('Match is featured: Final detected in group', [
                    'home_team' => $match->home_team,
                    'away_team' => $match->away_team,
                    'group' => $match->group,
                ]);
                return true;
            }
        }

        return false;
    }
}
