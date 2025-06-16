<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendChatPushNotification;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'question_id',
        'message'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function seenBy()
    {
        return $this->belongsToMany(User::class, 'chat_message_user')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }

    public function markAsRead(User $user)
    {
        $this->seenBy()->syncWithoutDetaching([
            $user->id => [
                'is_read' => true,
                'read_at' => now()
            ]
        ]);
    }

    public function getUnreadCount(User $user)
    {
        return $this->whereDoesntHave('seenBy', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
    }

    public function sendPushNotification()
    {
        SendChatPushNotification::dispatch($this->id);
    }
}
