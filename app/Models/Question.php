<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'points',
        'available_until',
        'group_id',
        'match_id',
        'is_featured',
        'category',
        'user_id',
        'template_question_id',
        'competition_id',
    ];

    protected $casts = [
        'available_until' => 'datetime',
        'is_featured' => 'boolean'
    ];

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function football_match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function templateQuestion()
    {
        return $this->belongsTo(TemplateQuestion::class);
    }
}
