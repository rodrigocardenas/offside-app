<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

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
        'result_verified_at',
    ];

    protected $casts = [
        'available_until' => 'datetime',
        'is_featured' => 'boolean',
        'result_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        /**
         * BUG #6 FIX: Validación adicional para evitar preguntas predictivas duplicadas
         * Verifica que no exista otra pregunta predictiva para el mismo match/group
         * creada en las últimas 24 horas (aunque esté expirada)
         */
        static::creating(function ($question) {
            if ($question->type === 'predictive' && $question->match_id && $question->group_id) {
                $existingQuestion = self::where('type', 'predictive')
                    ->where('group_id', $question->group_id)
                    ->where('match_id', $question->match_id)
                    ->where('created_at', '>', now()->subHours(24))
                    ->first();

                if ($existingQuestion) {
                    Log::warning('Attempt to create duplicate predictive question', [
                        'match_id' => $question->match_id,
                        'group_id' => $question->group_id,
                        'existing_question_id' => $existingQuestion->id,
                        'new_question_title' => $question->title
                    ]);

                    throw new \Exception(
                        "Una pregunta predictiva para el partido {$question->match_id} " .
                        "ya existe en el grupo {$question->group_id}"
                    );
                }
            }
        });
    }

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

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
