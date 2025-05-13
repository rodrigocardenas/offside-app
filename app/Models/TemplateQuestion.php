<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'text',
        'options',
        'is_featured',
        'likes',
        'dislikes',
        'competition_id', // Nueva columna
        'used_at',        // Nueva columna
    ];

    protected $casts = [
        'options' => 'array',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'used_at' => 'datetime', // Cast para el timestamp
    ];

    protected $attributes = [
        'likes' => 0,
        'dislikes' => 0,
    ];

    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    public function setOptionsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['options'] = json_encode($value);
        } else {
            $this->attributes['options'] = $value;
        }
    }

    /**
     * Relación con la tabla de competiciones.
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}
