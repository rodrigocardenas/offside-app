<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateQuestion extends Model
{
    protected $fillable = [
        'type',
        'text',
        'options',
        'is_featured',
        'likes',
        'dislikes'
    ];

    protected $casts = [
        'options' => 'array',
        'likes' => 'integer',
        'dislikes' => 'integer'
    ];
    
    protected $attributes = [
        'likes' => 0,
        'dislikes' => 0
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
}
