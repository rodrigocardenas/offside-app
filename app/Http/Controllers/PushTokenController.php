<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushTokenController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        // Guardar o actualizar el token en la relación pushSubscriptions
        $user->pushSubscriptions()->first()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_token' => $request->token
            ],
            [
                'device_token' => $request->token
            ]
        );

        return response()->json(['success' => true]);
    }
}
