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

        ChatMessage::create([
            'user_id' => auth()->id(),
            'group_id' => $group->id,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Mensaje enviado exitosamente.');
    }
}
