<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Jobs\SendChatPushNotification;

class ChatController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // Buscar mensaje igual en los últimos 5 segundos
        $existingMessage = ChatMessage::where('user_id', auth()->id())
            ->where('group_id', $group->id)
            ->where('message', $request->message)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();

        if ($existingMessage) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor, espera antes de enviar el mismo mensaje nuevamente.'
                ], 429);
            }
            return back()->with('error', 'Por favor, espera antes de enviar el mismo mensaje nuevamente.');
        }

        $message = ChatMessage::updateOrCreate([
            'user_id' => auth()->id(),
            'group_id' => $group->id,
            'message' => $request->message,
        ]);

        // Marcar el mensaje como leído por el remitente
        $message->markAsRead(auth()->user());

        // Limpiar todas las claves de caché relacionadas con el grupo
        $cacheKeys = [
            "group_{$group->id}_show_data",
            "group_{$group->id}_roles",
            "group_{$group->id}_social_question",
            "group.{$group->id}.chat_messages",
            "group.{$group->id}.unread_messages"
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        // Enviar notificación push
        SendChatPushNotification::dispatch($message->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'html' => view('partials.chat-message', ['message' => $message])->render()
            ]);
        }

        return back()->withFragment('chatForm');
    }

    public function markAsRead(Group $group)
    {
        $unreadMessages = $group->chatMessages()
            ->whereDoesntHave('seenBy', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsRead(auth()->user());
        }

        // Limpiar todas las claves de caché relacionadas con el grupo
        $cacheKeys = [
            "group_{$group->id}_show_data",
            "group_{$group->id}_roles",
            "group_{$group->id}_social_question",
            "group.{$group->id}.chat_messages",
            "group.{$group->id}.unread_messages"
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        return response()->json([
            'success' => true,
            'unread_count' => 0
        ]);
    }

    public function getUnreadCount(Group $group)
    {
        $unreadCount = $group->chatMessages()
            ->whereDoesntHave('seenBy', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->count();

        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}
