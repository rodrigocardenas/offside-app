<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreMatch extends Model
{
    use HasFactory;

    protected $fillable = ['football_match_id', 'group_id', 'created_by', 'penalty_type', 'penalty_points', 'penalty_description', 'status', 'admin_notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'football_match_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function propositions(): HasMany
    {
        return $this->hasMany(PreMatchProposition::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PreMatchVote::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(PreMatchResolution::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(GroupPenalty::class);
    }
}
