<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
        if (!$user) {
            abort(403, 'Usuario no autenticado');
        }
        // Asegurar que los roles estén cargados
        $user->load('roles');
        $competitions = Competition::orderBy('name')->get();

        // Load clubs for the selected competition
        $clubs = collect();
        if ($user->favorite_competition_id) {
            $clubs = Team::where('type', 'club')
                ->whereHas('competitions', function ($query) use ($user) {
                    $query->where('competitions.id', $user->favorite_competition_id);
                })
                ->orderBy('name')
                ->get();
        }

        $nationalTeams = Team::where('type', 'national')
            ->orderBy('name')
            ->get();

        return view('profile.edit', compact('user', 'competitions', 'clubs', 'nationalTeams'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        Log::info('ProfileController::update iniciado');

        $user = $request->user();
        $data = $request->validated();

        Log::info('Datos validados:', $data);

        // Manejar la subida del avatar
        if ($request->hasFile('avatar')) {
            Log::info('Archivo avatar detectado');

            if ($request->file('avatar')->isValid()) {
                Log::info('Archivo avatar es válido');

                try {
                    // Eliminar el avatar anterior si existe
                    if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                        Log::info('Eliminando avatar anterior: ' . $user->avatar);
                        Storage::disk('public')->delete('avatars/' . $user->avatar);
                    }

                    // Guardar el nuevo avatar usando un enfoque más simple
                    $avatarFile = $request->file('avatar');
                    Log::info('Información del archivo:', [
                        'original_name' => $avatarFile->getClientOriginalName(),
                        'extension' => $avatarFile->getClientOriginalExtension(),
                        'mime_type' => $avatarFile->getMimeType(),
                        'size' => $avatarFile->getSize()
                    ]);

                    // Usar un nombre simple basado en timestamp
                    $timestamp = time();
                    $extension = $avatarFile->getClientOriginalExtension() ?: 'jpg';
                    $avatarName = "avatar_{$timestamp}.{$extension}";

                    Log::info('Nombre del archivo: ' . $avatarName);

                    // Verificar que el directorio existe
                    $avatarPath = storage_path('app/public/avatars');
                    if (!is_dir($avatarPath)) {
                        Log::info('Creando directorio: ' . $avatarPath);
                        mkdir($avatarPath, 0755, true);
                    }

                    // Guardar usando move
                    $destination = $avatarPath . '/' . $avatarName;
                    Log::info('Destino: ' . $destination);

                    $avatarFile->move($avatarPath, $avatarName);
                    Log::info('Archivo movido exitosamente');

                    // 🔒 Fijar permisos correctos inmediatamente después
                    if (file_exists($destination)) {
                        chmod($destination, 0644);
                        Log::info('Permisos del archivo fijados a 644: ' . $destination);
                    }

                    $data['avatar'] = $avatarName;
                    Log::info('Avatar agregado a datos: ' . $avatarName);

                } catch (\Exception $e) {
                    Log::error('Error al procesar avatar: ' . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return Redirect::route('profile.edit')
                        ->withErrors(['avatar' => 'Error al subir la imagen: ' . $e->getMessage()]);
                }
            } else {
                Log::warning('Archivo avatar no es válido');
                return Redirect::route('profile.edit')
                    ->withErrors(['avatar' => 'El archivo de imagen no es válido.']);
            }
        } else {
            Log::info('No se detectó archivo avatar');
        }

        Log::info('Llenando datos del usuario');
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        Log::info('Guardando usuario');
        $user->save();
        Log::info('Usuario guardado exitosamente');

        $message = 'Perfil actualizado correctamente.';
        if (isset($data['avatar'])) {
            $message .= ' Avatar actualizado.';
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated')->with('success', $message);
    }

    /**
     * Get clubs by competition
     */
    public function getClubsByCompetition($competitionId)
    {
        $clubs = Team::where('type', 'club')
            ->whereHas('competitions', function ($query) use ($competitionId) {
                $query->where('competitions.id', $competitionId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($clubs);
    }

    /**
     * Get national teams
     */
    public function getNationalTeams()
    {
        $nationalTeams = Team::where('type', 'national')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($nationalTeams);
    }
}
