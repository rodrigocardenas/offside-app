<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreMatchVote extends Model
{
    use HasFactory;

    protected $fillable = ['pre_match_proposition_id', 'user_id', 'approved'];
    protected $casts = ['approved' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function proposition(): BelongsTo
    {
        return $this->belongsTo(PreMatchProposition::class, 'pre_match_proposition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot model - Disparar eventos cuando se vota
     */
    protected static function booted(): void
    {
        /**
         * Evento: Voto Registrado
         */
        static::created(function (self $vote) {
            $proposition = $vote->proposition;

            PreMatchEvent::create([
                'pre_match_id' => $proposition->pre_match_id,
                'event_type' => 'vote.created',
                'payload' => json_encode([
                    'proposition_id' => $proposition->id,
                    'voter_id' => $vote->user_id,
                    'voter_name' => $vote->user->name,
                    'voted_yes' => $vote->approved,
                    'approved_votes' => $proposition->approved_votes,
                    'votes_count' => $proposition->votes_count,
                    'approval_percentage' => $proposition->approval_percentage,
                    'proposition_creator_id' => $proposition->user_id,
                ]),
            ]);
        });
    }
}

