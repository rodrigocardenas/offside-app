<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        ]);

        // Buscar usuario por ID completo (unique_id)
        $user = User::where('unique_id', $request->name)->first();

        // Si no se encuentra por ID completo, crear un nuevo usuario
        if (!$user) {
            // Generar un username único basado en el nombre ingresado
            $baseName = $request->name;
            $counter = 1;
            $uniqueName = $baseName;

            // Verificar si el nombre base ya existe y generar uno único
            while (User::where('name', $uniqueName)->exists()) {
                $uniqueName = $baseName . $counter;
                $counter++;
            }

            // Crear nuevo usuario con el nombre único
            $user = User::create([
                'name' => $uniqueName,
                'email' => $uniqueName . '@offsideclub.com',
                'password' => Hash::make(Str::random(16)),
            ]);
        }

        Auth::login($user);

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
