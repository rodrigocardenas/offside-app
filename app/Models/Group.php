<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'created_by',
        'competition_id',
        'category',
        'reward_or_penalty'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function groupRoles()
    {
        return $this->hasMany(GroupRole::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function templateQuestions()
    {
        return $this->hasMany(TemplateQuestion::class);
    }

    /**
     * Get user's rank in the group
     *
     * @param int $userId
     * @return int|null
     */
    public function getUserRank($userId)
    {
        // Get all users with their points in this group
        $rankedUsers = $this->users()
            ->select('users.id')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->pluck('users.id')
            ->toArray();

        $rank = array_search($userId, $rankedUsers);

        return $rank !== false ? $rank + 1 : null;
    }

    /**
     * Check if user has pending predictions in this group
     *
     * @param int $userId
     * @return bool
     */
    public function hasPendingPredictions($userId)
    {
        return $this->questions()
            ->where('available_until', '>', now())
            ->whereDoesntHave('answers', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Get ranked users with points
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function rankedUsers()
    {
        return $this->users()
            ->select('users.*')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->get();
    }
}
