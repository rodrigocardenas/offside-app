<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Buscar usuario por ID completo (username_id)
        $user = User::where('unique_id', $request->name)->first();

        // Si no se encuentra por ID completo, buscar por nombre base
        if (!$user) {
            $user = User::where('name', $request->name)->first();

            // Si no existe, crear nuevo usuario
            if (!$user) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->name . '@offsideclub.com',
                    'password' => Hash::make(Str::random(16)),
                ]);
            }
        }

        Auth::login($user);

        return redirect()->route('groups.index')
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
