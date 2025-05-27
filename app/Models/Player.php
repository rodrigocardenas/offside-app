<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'external_id',
        'name',
        'first_name',
        'last_name',
        'position',
        'nationality',
        'shirt_number',
        'market_value',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'shirt_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth?->age;
    }
}
