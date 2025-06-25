<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
        'points'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'available_until' => 'datetime',
        'points' => 'integer'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_option_id');
    }
}
