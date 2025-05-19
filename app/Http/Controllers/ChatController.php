<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Group;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = ChatMessage::create([
            'user_id' => auth()->id(),
            'group_id' => $group->id,
            'message' => $request->message,
        ]);

        // Marcar el mensaje como leído por el remitente
        $message->markAsRead(auth()->user());

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
