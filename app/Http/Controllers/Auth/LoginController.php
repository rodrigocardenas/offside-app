<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'timezone' => 'nullable|string|timezone',
        ]);

        $wasCreated = false;

        // Buscar usuario PRIMERO por nombre ('name'), LUEGO por unique_id
        // Esto previene crear múltiples usuarios con el mismo nombre
        $user = User::where('name', trim($request->name))->first();
        
        if (!$user) {
            // Si no existe por nombre, intentar por unique_id
            $user = User::where('unique_id', $request->name)->first();
        }

        // Si no se encuentra, crear un nuevo usuario
        if (!$user) {
            // Generar un username único basado en el nombre ingresado
            $baseName = trim($request->name);

            // Generar un correo único reutilizando el nombre base
            $emailDomain = '@offsideclub.com';
            $email = $baseName . $emailDomain;
            $counter = 1;

            while (User::where('email', $email)->exists()) {
                $email = $baseName . '_' . $counter . $emailDomain;
                $counter++;
            }

            // Determinar timezone
            $timezone = $request->timezone ?? config('app.timezone');

            // Crear nuevo usuario manteniendo el nombre original y correo único
            $user = User::create([
                'name' => $baseName,
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'timezone' => $timezone,
            ]);

            $wasCreated = true;
        } else {
            // Si el usuario existe, SIEMPRE actualizar el timezone si viene en la request
            // Esto asegura que se actualice aunque ya tenga un valor
            if ($request->filled('timezone')) {
                $user->update(['timezone' => $request->timezone]);
            }
        }

        Auth::login($user);

        if ($wasCreated) {
            event(new Registered($user));
        }

        // Log para debug
        Log::info('Usuario autenticado: ' . $user->name . ' (ID: ' . $user->id . ')');
        $intended = session('url.intended');
        Log::info('Intended URL: ' . $intended);
        $redirectUrl = $intended ?: '/';
        Log::info('Redirigiendo a: ' . $redirectUrl);

        return redirect($redirectUrl)
            ->with('success', '¡Bienvenido ' . $user->name . '! Tu ID completo es: ' . $user->unique_id);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
