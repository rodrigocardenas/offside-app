<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;

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
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Manejar la subida del avatar
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            try {
                // Eliminar el avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }

                // Guardar el nuevo avatar
                $avatarFile = $request->file('avatar');

                // Obtener la extensión del archivo
                $extension = $avatarFile->getClientOriginalExtension();
                if (empty($extension)) {
                    // Si no hay extensión, intentar obtenerla del MIME type
                    $mimeType = $avatarFile->getMimeType();
                    $extension = match($mimeType) {
                        'image/jpeg' => 'jpg',
                        'image/jpg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                }

                $avatarName = Str::uuid() . '.' . $extension;

                // Verificar que el directorio existe
                if (!Storage::disk('public')->exists('avatars')) {
                    Storage::disk('public')->makeDirectory('avatars');
                }

                $avatarFile->storeAs('avatars', $avatarName, 'public');

                $data['avatar'] = $avatarName;

            } catch (\Exception $e) {
                return Redirect::route('profile.edit')
                    ->withErrors(['avatar' => 'Error al subir la imagen: ' . $e->getMessage()]);
            }
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $message = 'Perfil actualizado correctamente.';
        if (isset($data['avatar'])) {
            $message .= ' Avatar actualizado.';
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated')->with('success', $message);
    }
}
