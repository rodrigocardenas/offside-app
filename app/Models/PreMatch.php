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
        return $this->belongsTo(FootballMatch::class, 'football_match_id', 'id');
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

    /**
     * Boot model - Disparar eventos cuando cambia el estado
     */
    protected static function booted(): void
    {
        /**
         * Evento: Cambio de Estado del Pre-Match
         */
        static::updating(function (self $preMatch) {
            if ($preMatch->isDirty('status')) {
                $oldStatus = $preMatch->getOriginal('status');
                $newStatus = $preMatch->status;

                PreMatchEvent::create([
                    'pre_match_id' => $preMatch->id,
                    'event_type' => 'status.changed',
                    'payload' => json_encode([
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'group_id' => $preMatch->group_id,
                    ]),
                ]);

                // Log especial para cuando pasa a ACTIVE
                if ($newStatus === 'active' && $oldStatus === 'pending') {
                    PreMatchEvent::create([
                        'pre_match_id' => $preMatch->id,
                        'event_type' => 'status.pending_to_active',
                        'payload' => json_encode([
                            'message' => 'Todas las propuestas fueron aprobadas',
                            'propositions_approved' => $preMatch->propositions()->where('validation_status', 'approved')->count(),
                        ]),
                    ]);
                }

                // Log especial para cuando pasa a RESOLVED
                if ($newStatus === 'resolved') {
                    PreMatchEvent::create([
                        'pre_match_id' => $preMatch->id,
                        'event_type' => 'status.resolved',
                        'payload' => json_encode([
                            'message' => 'Pre-match resuelto',
                            'resolved_at' => now()->toIso8601String(),
                        ]),
                    ]);
                }
            }
        });
    }
}
