<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'api_name',
        'type',
        'short_name',
        'tla',
        'external_id',
        'country',
        'crest_url',
        'website',
        'founded_year',
        'club_colors',
        'venue',
        'stadium_id',
    ];

    protected $casts = [
        'founded_year' => 'integer',
    ];

    public function stadium()
    {
        return $this->belongsTo(Stadium::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function homeMatches()
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    public function matches()
    {
        return $this->homeMatches->merge($this->awayMatches);
    }

    public function competitions()
    {
        return $this->belongsToMany(Competition::class);
    }
}
