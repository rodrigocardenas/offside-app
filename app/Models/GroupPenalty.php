<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPenalty extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'user_id', 'pre_match_id', 'penalty_type', 'penalty_data', 'penalty_description', 'is_resolved', 'admin_notes', 'resolved_at'];
    protected $casts = ['penalty_data' => 'json', 'is_resolved' => 'boolean', 'resolved_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preMatch(): BelongsTo
    {
        return $this->belongsTo(PreMatch::class);
    }
}
