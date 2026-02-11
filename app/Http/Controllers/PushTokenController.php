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
            'platform' => 'required|in:web,android,ios',
            'user_id' => 'required|integer|exists:users,id',
            'endpoint' => 'nullable|string',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        Log::info('Registrando token push', [
            'user_id' => $user->id,
            'platform' => $request->platform,
            'token' => substr($request->token, 0, 20) . '...'
        ]);

        // Guardar o actualizar el token en la relación pushSubscriptions
        $user->pushSubscriptions()->updateOrCreate(
            [
                'device_token' => $request->token,
            ],
            [
                'endpoint' => $request->endpoint,
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
                'platform' => $request->platform
            ]
        );

        return response()->json(['success' => true, 'message' => 'Token registrado correctamente']);
    }
}
