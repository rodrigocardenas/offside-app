<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FootballMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'home_team_id',
        'away_team_id',
        'stadium_id',
        'date',
        'status',
        'is_featured',
        'matchday',
        'stage',
        'group',
        'home_team_score',
        'away_team_score',
        'home_team_penalties',
        'away_team_penalties',
        'winner',
        'duration',
        'referee',
        'competition_id',
        'season',
        'home_team',
        'away_team',
        'league',
        'competition',
        'match_date',
        'events',
        'statistics',
        'score',
        'last_verification_attempt_at',
        'verification_priority',
    ];

    protected $casts = [
        'date' => 'datetime',
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
        'home_team_penalties' => 'integer',
        'away_team_penalties' => 'integer',
        'matchday' => 'string',
        'is_featured' => 'boolean',
        'match_date' => 'datetime',
        'last_verification_attempt_at' => 'datetime',
        'verification_priority' => 'integer',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team', 'api_name');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team', 'api_name');
    }

    public function stadium()
    {
        return $this->belongsTo(Stadium::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'match_id');
    }

    public function getCompetitionNameAttribute()
    {
        $competitions = [
            'PL' => 'Premier League',
            'PD' => 'La Liga',
            'SA' => 'Serie A',
            'BL1' => 'Bundesliga',
            'FL1' => 'Ligue 1',
            'DED' => 'Eredivisie',
            'PPL' => 'Primeira Liga',
            'CL' => 'UEFA Champions League',
            'ELC' => 'UEFA Europa League',
            'UEL' => 'UEFA Europa Conference League',
            'WC' => 'FIFA World Cup',
            'EC' => 'UEFA European Championship',
            'FAC' => 'FA Cup',
            'DFB' => 'DFB-Pokal',
            'CUP' => 'Copa del Rey',
        ];

        return $competitions[$this->league] ?? 'Otro';
    }

    // Removed: This accessor was preventing score column from being saved
    // The 'score' column now stores the text representation (e.g., "2 - 0")
    // Use home_team_score and away_team_score directly for structured data

    /**
     * Determinar si las preguntas creadas para este match deben ser marcadas como featured.
     * Retorna TRUE si al menos uno de los equipos es featured.
     * 
     * @return bool TRUE si merece preguntas destacadas, FALSE en caso contrario
     */
    public function getQuestionFeaturedValue(): bool
    {
        $homeIsFeatured = $this->homeTeam?->is_featured ?? false;
        $awayIsFeatured = $this->awayTeam?->is_featured ?? false;
        
        return $homeIsFeatured || $awayIsFeatured;
    }

    /**
     * Calcular score de prioridad basado en equipos destacados.
     * - 1.0: Ambos equipos destacados (Clásico)
     * - 0.7: Un equipo destacado (Derby)
     * - 0.3: Ningún equipo destacado (Partido regular)
     * 
     * @return float Score de prioridad (1.0, 0.7, o 0.3)
     */
    public function getFeaturedPriorityScore(): float
    {
        $homeIsFeatured = $this->homeTeam?->is_featured ?? false;
        $awayIsFeatured = $this->awayTeam?->is_featured ?? false;
        
        if ($homeIsFeatured && $awayIsFeatured) {
            return 1.0; // Clásico
        } elseif ($homeIsFeatured || $awayIsFeatured) {
            return 0.7; // Derby
        }
        
        return 0.3; // Partido regular
    }

    /**
     * Scope: Obtener partidos ordenados por equipos destacados (prioridad mayor primero).
     * Ordena por: 1) Priority score DESC, 2) Date DESC
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFeaturedTeams($query)
    {
        return $query
            ->leftJoin('teams as home_teams', 'football_matches.home_team', '=', 'home_teams.api_name')
            ->leftJoin('teams as away_teams', 'football_matches.away_team', '=', 'away_teams.api_name')
            ->selectRaw('football_matches.*')
            ->selectRaw('
                CASE 
                    WHEN home_teams.is_featured = 1 AND away_teams.is_featured = 1 THEN 1.0
                    WHEN home_teams.is_featured = 1 OR away_teams.is_featured = 1 THEN 0.7
                    ELSE 0.3
                END as featured_priority
            ')
            ->orderByDesc('featured_priority')
            ->orderByDesc('date');
    }
}
