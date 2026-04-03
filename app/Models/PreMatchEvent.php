<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreMatchEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_match_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Relación con PreMatch
     */
    public function preMatch()
    {
        return $this->belongsTo(PreMatch::class);
    }
}
