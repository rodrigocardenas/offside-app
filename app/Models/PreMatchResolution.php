<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreMatchResolution extends Model
{
    use HasFactory;

    protected $fillable = ['pre_match_id', 'winning_pre_match_proposition_id', 'was_fulfilled', 'admin_verified', 'admin_evidence', 'admin_notes', 'resolved_at'];
    protected $casts = ['was_fulfilled' => 'boolean', 'admin_verified' => 'boolean', 'resolved_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function preMatch(): BelongsTo
    {
        return $this->belongsTo(PreMatch::class);
    }

    public function winningProposition(): BelongsTo
    {
        return $this->belongsTo(PreMatchProposition::class, 'winning_pre_match_proposition_id');
    }
}
