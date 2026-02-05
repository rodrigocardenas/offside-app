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
        'crest_url',
    ];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function matches()
    {
        return $this->hasMany(FootballMatch::class);
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
        return $this->belongsToMany(Team::class);
    }
}
