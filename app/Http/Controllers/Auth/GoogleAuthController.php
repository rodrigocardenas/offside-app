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
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir a Google para autenticación
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Manejar callback de Google OAuth
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect('/login')->with('error', 'OAuth validation failed');
        }

        // Buscar usuario por google_id
        $user = User::where('google_id', $googleUser->id)->first();

        if (!$user) {
            // Google user no existe en BD, intentar encontrar por email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Usuario existe con ese email, vincular Google OAuth
                $user->update([
                    'google_id' => $googleUser->id,
                    'google_email' => $googleUser->email,
                    'auth_provider' => 'google',
                ]);

                // Actualizar avatar si no tiene
                if (!$user->avatar && $googleUser->avatar) {
                    $user->update(['avatar' => $googleUser->avatar]);
                }
            } else {
                // Crear nuevo usuario
                $user = $this->createGoogleUser($googleUser);
            }
        } else {
            // Usuario google ya existe, solo actualizar avatar si es necesario
            if (!$user->avatar && $googleUser->avatar) {
                $user->update(['avatar' => $googleUser->avatar]);
            }
            
            // Marcar que último login fue por Google
            $user->update(['auth_provider' => 'google']);
        }

        // Hacer login del usuario
        Auth::login($user, remember: true);

        return redirect('/')->with('success', 'Logged in with Google!');
    }

    /**
     * Crear nuevo usuario desde Google
     */
    private function createGoogleUser($googleUser)
    {
        // Generar nombre único desde email de Google
        $baseName = trim(explode('@', $googleUser->email)[0]);

        // Generar email único si es necesario
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
            'password' => Hash::make(Str::random(16)), // Contraseña aleatoria
            'auth_provider' => 'google',
            'timezone' => 'UTC',
        ]);

        event(new Registered($user));

        return $user;
    }
}
