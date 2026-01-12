<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class GeminiAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gemini_analyses';

    protected $fillable = [
        'football_match_id',
        'analysis_type',
        'analysis_data',
        'summary',
        'grounding_sources',
        'confidence_score',
        'tokens_used',
        'processing_time_ms',
        'status',
        'error_message',
        'attempt_count',
        'user_id',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'grounding_sources' => 'array',
        'confidence_score' => 'float',
        'tokens_used' => 'integer',
        'processing_time_ms' => 'integer',
        'attempt_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con FootballMatch
     */
    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'football_match_id');
    }

    /**
     * Relación con User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar por tipo de análisis
     */
    public function scopeByType($query, $type)
    {
        return $query->where('analysis_type', $type);
    }

    /**
     * Scope para análisis recientes
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope para análisis completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para análisis fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Marcar como completado
     */
    public function markCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Marcar como procesando
     */
    public function markProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Marcar como fallido
     */
    public function markFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Incrementar contador de intentos
     */
    public function incrementAttempts()
    {
        $this->increment('attempt_count');
    }
}

