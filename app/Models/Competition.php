<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'country',
    ];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function templateQuestions()
    {
        return $this->hasMany(TemplateQuestion::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'competition_id', 'id');
    }
}
