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
}
