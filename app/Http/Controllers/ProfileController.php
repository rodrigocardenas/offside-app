<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Competition;
use App\Models\Team;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = auth()->user();
        $competitions = Competition::all();

        // Si el usuario tiene una competencia favorita, solo mostramos los clubes de esa competencia
        if ($user->favorite_competition_id) {
            $clubs = Team::where('type', 'club')->get();
        } else {
            $clubs = collect(); // Lista vacía si no hay competencia seleccionada
        }

        $nationalTeams = Team::where('type', 'national')->get();

        return view('profile.edit', compact('user', 'competitions', 'clubs', 'nationalTeams'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favorite_competition_id' => 'nullable|exists:competitions,id',
            'favorite_club_id' => 'nullable|exists:teams,id',
            'favorite_national_team_id' => 'nullable|exists:teams,id',
        ]);

        // Actualizar avatar si se proporciona uno nuevo
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }

            $avatarName = time() . '.' . $request->avatar->extension();
            $request->avatar->storeAs('public/avatars', $avatarName);
            $validated['avatar'] = $avatarName;
        }

        $user->update($validated);

        return redirect()->route('profile.edit')->with('success', 'Perfil actualizado correctamente');
    }
}
