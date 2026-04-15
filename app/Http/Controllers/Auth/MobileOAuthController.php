<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MobileOAuthController extends Controller
{
    /**
     * Endpoint para obtener URL de Google OAuth (para Capacitor)
     * Devuelve la URL de consentimiento sin hacer redirect
     */
    public function getGoogleAuthUrl(Request $request)
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get auth URL: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Endpoint para móvil: recibe credenciales de Google y maneja login directo
     * Este endpoint NO requiere state validation (es para Capacitor)
     */
    public function mobileGoogleLogin(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Buscar usuario por google_id
            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                // Google user no existe, intentar por email
                $user = User::where('email', $googleUser->email)->first();

                if ($user) {
                    // Vincular Google OAuth
                    $user->update([
                        'google_id' => $googleUser->id,
                        'google_email' => $googleUser->email,
                        'auth_provider' => 'google',
                    ]);

                    if (!$user->avatar && $googleUser->avatar) {
                        $user->update(['avatar' => $googleUser->avatar]);
                    }
                } else {
                    // Crear nuevo usuario
                    $user = $this->createGoogleUser($googleUser, $request->timezone ?? 'UTC');
                }
            } else {
                // Usuario existe, actualizar avatar si es necesario
                if (!$user->avatar && $googleUser->avatar) {
                    $user->update(['avatar' => $googleUser->avatar]);
                }
                $user->update(['auth_provider' => 'google']);
            }

            // Hacer login del usuario
            Auth::login($user, remember: true);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'redirect' => '/',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OAuth login failed: ' . $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Crear nuevo usuario desde Google
     */
    private function createGoogleUser($googleUser, $timezone = 'UTC')
    {
        $baseName = trim(explode('@', $googleUser->email)[0]);

        $email = $googleUser->email;
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $baseName . '_' . $counter . '@offsideclub.com';
            $counter++;
        }

        $user = User::create([
            'name' => $googleUser->name ?? $baseName,
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'google_email' => $googleUser->email,
            'avatar' => $googleUser->avatar,
            'password' => Hash::make(Str::random(16)),
            'auth_provider' => 'google',
            'timezone' => $timezone,
        ]);

        event(new Registered($user));

        return $user;
    }
}
