<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PushTokenController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'endpoint' => 'required|string',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        Log::info('user', ['user' => $user]);

        // Guardar o actualizar el token en la relación pushSubscriptions
        $user->pushSubscriptions()->updateOrCreate(
            [
                'endpoint' => $request->endpoint,
            ],
            [
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
                'device_token' => $request->token,
            ]
        );

        return response()->json(['success' => true]);
    }
}
