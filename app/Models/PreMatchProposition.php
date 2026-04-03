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

    /**
     * Boot model - Disparar eventos cuando se crean o eliminan proposiciones
     */
    protected static function booted(): void
    {
        /**
         * Evento: Proposición Creada
         */
        static::created(function (self $proposition) {
            PreMatchEvent::create([
                'pre_match_id' => $proposition->pre_match_id,
                'event_type' => 'proposition.created',
                'payload' => json_encode([
                    'proposition_id' => $proposition->id,
                    'user_id' => $proposition->user_id,
                    'user_name' => $proposition->user->name,
                    'user_avatar' => $proposition->user->getAvatarUrl('small'),
                    'action' => $proposition->action,
                    'description' => $proposition->description,
                    'approval_percentage' => 0,
                    'approved_votes' => 0,
                    'votes_count' => 0,
                ]),
            ]);
        });

        /**
         * Evento: Proposición Eliminada
         */
        static::deleted(function (self $proposition) {
            PreMatchEvent::create([
                'pre_match_id' => $proposition->pre_match_id,
                'event_type' => 'proposition.deleted',
                'payload' => json_encode([
                    'proposition_id' => $proposition->id,
                    'user_id' => $proposition->user_id,
                    'user_name' => $proposition->user->name,
                    'action' => $proposition->action,
                ]),
            ]);
        });

        /**
         * Evento: Proposición Auto-aprobada (cuando todos votan sí)
         */
        static::updating(function (self $proposition) {
            // Verificar si el status cambió a 'approved'
            if ($proposition->isDirty('validation_status') && $proposition->validation_status === 'approved') {
                PreMatchEvent::create([
                    'pre_match_id' => $proposition->pre_match_id,
                    'event_type' => 'proposition.auto_approved',
                    'payload' => json_encode([
                        'proposition_id' => $proposition->id,
                        'action' => $proposition->action,
                        'approved_votes' => $proposition->approved_votes,
                        'votes_count' => $proposition->votes_count,
                    ]),
                ]);
            }
        });
    }
}

