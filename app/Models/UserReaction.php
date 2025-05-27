<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_question_id',
        'reaction'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function templateQuestion()
    {
        return $this->belongsTo(TemplateQuestion::class);
    }
}
