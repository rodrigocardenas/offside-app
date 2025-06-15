<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Factory;

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
        $groupUsers = $this->group->users()->where('id', '!=', $this->user_id)->get();

        // Instancia de Firebase Messaging
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials') ?? base_path(env('FIREBASE_CREDENTIALS')));
        $messaging = $factory->createMessaging();

        foreach ($groupUsers as $user) {
            foreach ($user->pushSubscriptions as $subscription) {
                $message = [
                    'notification' => [
                        'title' => 'Nuevo mensaje en el grupo ' . $this->group->name,
                        'body' => $this->user->name . ': ' . $this->message,
                        'icon' => '/icon-192x192.png',
                        'click_action' => url('/groups/' . $this->group->id . '#chatSection'),
                    ],
                    'webpush' => [
                        'fcm_options' => [
                            'link' => url('/groups/' . $this->group->id . '#chatSection'),
                        ],
                    ],
                    'token' => $subscription->device_token,
                ];

                try {
                    $messaging->send($message);
                } catch (\Throwable $e) {
                    \Log::error('Error enviando notificación FCM: ' . $e->getMessage());
                }
            }
        }
    }
}
