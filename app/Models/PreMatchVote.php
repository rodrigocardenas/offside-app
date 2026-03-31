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
}
