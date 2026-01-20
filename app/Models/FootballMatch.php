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
        return $this->belongsTo(Team::class, 'away_team', 'name');
    }

    public function stadium()
    {
        return $this->belongsTo(Stadium::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function getScoreAttribute()
    {
        if ($this->status === 'FINISHED') {
            return [
                'home' => $this->home_team_score,
                'away' => $this->away_team_score,
                'penalties' => $this->home_team_penalties || $this->away_team_penalties
                    ? [
                        'home' => $this->home_team_penalties,
                        'away' => $this->away_team_penalties,
                    ]
                    : null,
            ];
        }
        return null;
    }
}
