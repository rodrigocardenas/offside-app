<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'text',
        'type',
        'options',
        'is_featured',
        'competition_id',
        'home_team_id',
        'away_team_id',
        'football_match_id',
        'match_date',
        'used_at'
    ];

    protected $casts = [
        'options' => 'array',
        'is_featured' => 'boolean',
        'match_date' => 'datetime',
        'used_at' => 'datetime'
    ];

    protected $attributes = [
        'likes' => 0,
        'dislikes' => 0,
    ];

    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    public function setOptionsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['options'] = json_encode($value);
        } else {
            $this->attributes['options'] = $value;
        }
    }

    /**
     * Relación con la tabla de competiciones.
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function footballMatch()
    {
        return $this->belongsTo(FootballMatch::class);
    }

    public function userReactions()
    {
        return $this->belongsToMany(User::class, 'template_question_user_reaction')
            ->withPivot('reaction')
            ->withTimestamps();
    }

    public function getLikesCount()
    {
        return $this->userReactions()->where('template_question_user_reaction.reaction', 'like')->count();
    }

    public function getDislikesCount()
    {
        return $this->userReactions()->where('template_question_user_reaction.reaction', 'dislike')->count();
    }

    public function hasUserReaction(User $user)
    {
        return $this->userReactions()->where('user_id', $user->id)->exists();
    }

    public function getUserReaction(User $user)
    {
        return $this->userReactions()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ->reaction;
    }
}
