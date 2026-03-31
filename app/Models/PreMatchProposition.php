<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreMatchProposition extends Model
{
    use HasFactory;

    protected $fillable = ['pre_match_id', 'user_id', 'action', 'description', 'validation_status', 'approval_percentage', 'votes_count', 'approved_votes'];
    protected $casts = ['approval_percentage' => 'decimal:2', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function preMatch(): BelongsTo
    {
        return $this->belongsTo(PreMatch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PreMatchVote::class);
    }
}
